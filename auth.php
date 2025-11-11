<?php
require_once __DIR__ . '/env.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const PIN_SESSION_KEY = 'pin_verified';
const PIN_SESSION_TIME_KEY = 'pin_verified_time';
const PIN_SESSION_VALUE_KEY = 'pin_verified_value';
const PIN_SESSION_TTL = 60 * 60 * 12; // 12 heures (utilisé uniquement pour valider la saisie immédiate)

const GOOGLE_SESSION_KEY = 'google_user';

if (!function_exists('google_get_config')) {
    function google_get_config(): array
    {
        static $config = null;
        if ($config !== null) {
            return $config;
        }

        $path = __DIR__ . '/google-config.php';
        if (!file_exists($path)) {
            throw new RuntimeException('Le fichier google-config.php est introuvable.');
        }

        $config = require $path;

        if (empty($config['client_id']) || stripos($config['client_id'], 'CLIENT_ID') !== false) {
            throw new RuntimeException('Veuillez configurer google-config.php avec un client_id OAuth 2.0 valide.');
        }

        return $config;
    }
}

if (!function_exists('google_is_authenticated')) {
    function google_is_authenticated(): bool
    {
        return !empty($_SESSION[GOOGLE_SESSION_KEY]) && is_array($_SESSION[GOOGLE_SESSION_KEY]);
    }
}

if (!function_exists('google_current_user')) {
    function google_current_user(): ?array
    {
        return google_is_authenticated() ? $_SESSION[GOOGLE_SESSION_KEY] : null;
    }
}

if (!function_exists('google_logout')) {
    function google_logout(): void
    {
        unset($_SESSION[GOOGLE_SESSION_KEY]);
        session_regenerate_id(true);
    }
}

if (!function_exists('google_store_user')) {
    function google_store_user(array $payload): void
    {
        $user = [
            'sub' => $payload['sub'] ?? '',
            'email' => $payload['email'] ?? '',
            'name' => $payload['name'] ?? ($payload['email'] ?? ''),
            'picture' => $payload['picture'] ?? '',
            'hd' => $payload['hd'] ?? '',
            'iat' => $payload['iat'] ?? null,
            'exp' => $payload['exp'] ?? null,
        ];

        session_regenerate_id(true);
        $_SESSION[GOOGLE_SESSION_KEY] = $user;
    }
}

if (!function_exists('google_verify_id_token')) {
    function google_verify_id_token(string $idToken): array
    {
        if ($idToken === '') {
            return ['success' => false, 'message' => 'Jeton Google manquant.'];
        }

        $config = google_get_config();
        $clientId = $config['client_id'] ?? '';

        $endpoint = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);

        $response = google_http_get_json($endpoint);
        if ($response === null) {
            return ['success' => false, 'message' => 'Impossible de vérifier le jeton Google.'];
        }

        if (($response['aud'] ?? '') !== $clientId) {
            return ['success' => false, 'message' => 'Jeton Google invalide (client non reconnu).'];
        }

        if (isset($response['exp']) && (int) $response['exp'] < time()) {
            return ['success' => false, 'message' => 'Le jeton Google a expiré.'];
        }

        if (!isset($response['email'])) {
            return ['success' => false, 'message' => 'Adresse e-mail manquante dans le jeton Google.'];
        }

        $email = strtolower($response['email']);
        $domain = substr(strrchr($email, '@') ?: '', 1);

        $allowedDomains = array_map('strtolower', $config['allowed_domains'] ?? []);
        $allowedEmails = array_map('strtolower', $config['allowed_emails'] ?? []);

        if (!empty($allowedEmails) && !in_array($email, $allowedEmails, true)) {
            return ['success' => false, 'message' => 'Adresse e-mail non autorisée.'];
        }

        if (!empty($allowedDomains) && (!in_array($domain, $allowedDomains, true))) {
            return ['success' => false, 'message' => 'Domaine e-mail non autorisé.'];
        }

        return ['success' => true, 'payload' => $response];
    }
}

if (!function_exists('google_http_get_json')) {
    function google_http_get_json(string $url): ?array
    {
        $response = null;

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
            ]);
            $body = curl_exec($ch);
            curl_close($ch);
            if ($body !== false) {
                $response = $body;
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                ],
            ]);
            $body = @file_get_contents($url, false, $context);
            if ($body !== false) {
                $response = $body;
            }
        }

        if ($response === null) {
            return null;
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : null;
    }
}

if (!function_exists('google_enforce_authentication')) {
    function google_enforce_authentication(): void
    {
        $allowedPaths = [
            '/login.php',
            '/api.php/google-login',
            '/api.php/google-logout',
        ];

        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
        $isApiRequest = $scriptName === 'api.php';

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return;
        }

        if (in_array($currentPath, $allowedPaths, true)) {
            return;
        }

        if (google_is_authenticated()) {
            return;
        }

        if ($isApiRequest) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Authentification Google requise.'
            ]);
            exit;
        }

        $redirect = $_SERVER['REQUEST_URI'] ?? 'index.php';
        $location = 'login.php?redirect=' . urlencode($redirect);
        header('Location: ' . $location);
        exit;
    }
}

if (!defined('AUTH_ALLOW_GOOGLE_PUBLIC')) {
    try {
        google_enforce_authentication();
    } catch (RuntimeException $exception) {
        if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'api.php') {
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $exception->getMessage()
            ]);
        } else {
            http_response_code(500);
            echo '<h1>Configuration Google requise</h1>';
            echo '<p>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
        }
        exit;
    }
}

if (!function_exists('pin_is_validated')) {
    function pin_is_validated(): bool
    {
        if (empty($_SESSION[PIN_SESSION_KEY])) {
            return false;
        }

        $verifiedAt = $_SESSION[PIN_SESSION_TIME_KEY] ?? 0;
        if ($verifiedAt <= 0) {
            return false;
        }

        if (PIN_SESSION_TTL > 0 && (time() - $verifiedAt) > PIN_SESSION_TTL) {
            pin_clear_validation();
            return false;
        }

        return true;
    }
}

if (!function_exists('pin_require')) {
    function pin_require(?string $redirectTarget = null): void
    {
        if (!pin_is_validated()) {
            $pinRedirect = $redirectTarget ?? ($_SERVER['REQUEST_URI'] ?? 'index.php');
            include 'pin-gate.php';
            exit;
        }

        // Le PIN doit être redemandé à chaque accès : on nettoie immédiatement la validation.
        pin_clear_validation();
    }
}

if (!function_exists('pin_mark_validated')) {
    function pin_mark_validated(string $pinValue = ''): void
    {
        $_SESSION[PIN_SESSION_KEY] = true;
        $_SESSION[PIN_SESSION_TIME_KEY] = time();
        if ($pinValue !== '') {
            $_SESSION[PIN_SESSION_VALUE_KEY] = $pinValue;
        }
    }
}

if (!function_exists('pin_clear_validation')) {
    function pin_clear_validation(): void
    {
        unset($_SESSION[PIN_SESSION_KEY], $_SESSION[PIN_SESSION_TIME_KEY], $_SESSION[PIN_SESSION_VALUE_KEY]);
    }
}
