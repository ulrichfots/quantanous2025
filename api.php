<?php
require_once 'auth.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Charger les helpers
require_once 'back4app-helper.php';
require_once 'stripe-helper.php';
require_once 'email-helper.php';

const DEFAULT_PIN_CODE = '271244';

if (!function_exists('normalize_project_title')) {
    function normalize_project_title(string $title): string
    {
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT', $title);
        if ($transliterated === false) {
            $transliterated = $title;
        }

        $lower = mb_strtolower($transliterated, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9]+/', '-', $lower);
        $slug = trim($slug, '-');

        if ($slug === '') {
            $fallback = mb_strtolower($title, 'UTF-8');
            $slug = trim(preg_replace('/\s+/', '-', $fallback), '-');
        }

        return $slug;
    }
}

function getStoredPin(Back4AppHelper $back4app): array
{
    $pinResult = $back4app->get('Pin', null, 1, '-createdAt');
    if (($pinResult['success'] ?? false) && isset($pinResult['data']['results'][0])) {
        $pinObject = $pinResult['data']['results'][0];
        $pin = $pinObject['pin'] ?? null;
        $objectId = $pinObject['objectId'] ?? null;
        if (!empty($pin)) {
            return ['pin' => (string) $pin, 'objectId' => $objectId];
        }
    }
    return ['pin' => null, 'objectId' => null];
}

$back4app = new Back4AppHelper();

$stripe = null;
try {
    $stripe = new StripeHelper();
} catch (Throwable $e) {
    error_log('StripeHelper: ' . $e->getMessage());
}

$emailHelper = null;
try {
    $emailHelper = new EmailHelper();
} catch (Throwable $e) {
    error_log('EmailHelper: ' . $e->getMessage());
}

// Router simple pour les API
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api.php', '', $path);

// Gestion CORS preflight
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($path === '/google-login' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $credential = $input['credential'] ?? '';
    $redirectTarget = $input['redirect'] ?? 'index.php';

    try {
        $verification = google_verify_id_token($credential);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
        ]);
        exit;
    }

    if (!($verification['success'] ?? false)) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => $verification['message'] ?? 'Authentification Google refusée.'
        ]);
        exit;
    }

    google_store_user($verification['payload']);
    pin_clear_validation();

    echo json_encode([
        'status' => 'success',
        'redirect' => $redirectTarget,
        'user' => google_current_user(),
    ]);
    exit;
}

if ($path === '/google-logout' && $method === 'POST') {
    google_logout();
    pin_clear_validation();

    echo json_encode([
        'status' => 'success',
        'message' => 'Déconnecté avec succès.',
    ]);
    exit;
}

// Route de test
if ($path === '/test' && $method === 'GET') {
    echo json_encode([
        'status' => 'success',
        'message' => 'Test API réussi',
        'data' => [
            'method' => $method,
            'timestamp' => time()
        ]
    ]);
    exit;
}

// Configuration Stripe
if ($path === '/get-stripe-config' && $method === 'GET') {
    if ($stripe === null) {
        http_response_code(500);
        $errorDetails = 'Stripe n\'est pas configuré.';
        
        // Vérifier si les variables d'environnement sont définies
        $hasPublishableKey = !empty(getenv('STRIPE_PUBLISHABLE_KEY'));
        $hasSecretKey = !empty(getenv('STRIPE_SECRET_KEY'));
        
        if (!$hasPublishableKey || !$hasSecretKey) {
            $missing = [];
            if (!$hasPublishableKey) $missing[] = 'STRIPE_PUBLISHABLE_KEY';
            if (!$hasSecretKey) $missing[] = 'STRIPE_SECRET_KEY';
            $errorDetails .= ' Variables manquantes: ' . implode(', ', $missing);
        }
        
        echo json_encode([
            'status' => 'error',
            'message' => $errorDetails
        ]);
        exit;
    }

    try {
        $publishableKey = $stripe->getPublishableKey();
        if (empty($publishableKey)) {
            throw new RuntimeException('Clé publique Stripe vide');
        }
        
        echo json_encode([
            'status' => 'success',
            'publishable_key' => $publishableKey
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Erreur lors de la récupération de la clé Stripe: ' . $e->getMessage()
        ]);
    }
    exit;
}

if ($path === '/pin-status' && $method === 'GET') {
    echo json_encode([
        'status' => 'success',
        'verified' => pin_is_validated()
    ]);
    exit;
}

// Vérification du PIN
if ($path === '/verify-pin' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $pin = isset($input['pin']) ? trim((string) $input['pin']) : '';

    if ($pin === '') {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'PIN manquant'
        ]);
        exit;
    }

    if (!preg_match('/^\d{6}$/', $pin)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Le code PIN doit contenir exactement 6 chiffres'
        ]);
        exit;
    }

    $stored = getStoredPin($back4app);
    $currentPin = $stored['pin'] ?? null;
    if ($currentPin === null) {
        $currentPin = DEFAULT_PIN_CODE;
    }

    if (hash_equals((string) $currentPin, $pin)) {
        pin_mark_validated($currentPin);
        echo json_encode([
            'status' => 'success',
            'message' => 'PIN validé'
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'PIN invalide'
        ]);
    }
    exit;
}

// Création d'un Payment Intent (paiement intégré, sans redirection)
if ($path === '/create-payment-intent' && $method === 'POST') {
    if ($stripe === null) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Stripe n\'est pas configuré. Vérifiez stripe-config.php.'
        ]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $amount = isset($input['montant']) ? (float) $input['montant'] : 0;
    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Montant invalide'
        ]);
        exit;
    }

    $from = $input['from'] ?? '';
    $frequency = $input['frequency'] ?? '';
    $articleId = $input['article_id'] ?? '';
    $customerEmail = $input['email'] ?? null;
    $frequencyInterval = $input['frequency_interval'] ?? '';
    $frequencyIntervalCount = isset($input['frequency_interval_count']) ? (int) $input['frequency_interval_count'] : null;
    $frequencyLabel = $input['frequency_label'] ?? '';

    $metadata = [
        'nom' => $input['nom'] ?? '',
        'prenom' => $input['prenom'] ?? '',
        'adresse' => $input['adresse'] ?? '',
        'code_postal' => $input['code_postal'] ?? '',
        'ville' => $input['ville'] ?? '',
        'article_id' => $articleId,
        'source' => $from,
    ];

    // Vérifier si c'est un don récurrent
    $isRecurring = ($from === 'don' && $frequency === 'regulier');

    if ($isRecurring) {
        // Pour les dons récurrents, créer un Setup Intent
        $intervalMapByLabel = [
            'mensuel' => ['month', 1],
            'trimestriel' => ['month', 3],
            'semestriel' => ['month', 6],
            'annuel' => ['year', 1],
        ];

        $normalizedLabel = strtolower($frequencyLabel);
        if (isset($intervalMapByLabel[$normalizedLabel])) {
            [$recurringInterval, $recurringIntervalCount] = $intervalMapByLabel[$normalizedLabel];
        }

        $allowedIntervals = ['day', 'week', 'month', 'year'];
        if (!in_array($frequencyInterval, $allowedIntervals, true)) {
            $frequencyInterval = '';
        }

        if ($frequencyInterval && $frequencyIntervalCount) {
            $recurringInterval = $frequencyInterval;
            $recurringIntervalCount = max(1, $frequencyIntervalCount);
        }

        if (!$recurringInterval) {
            $recurringInterval = 'month';
            $recurringIntervalCount = 1;
        }

        $metadata['type'] = 'don_regulier';
        $metadata['frequency'] = $frequencyLabel;
        $metadata['frequency_interval'] = $recurringInterval;
        $metadata['frequency_interval_count'] = $recurringIntervalCount;
        $metadata['frequency_label'] = $frequencyLabel;

        $setupIntent = $stripe->createSetupIntent([
            'customer_email' => $customerEmail,
            'metadata' => array_filter($metadata, fn($value) => $value !== null && $value !== ''),
        ]);

        if (!$setupIntent['success']) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $setupIntent['error'] ?? 'Erreur Stripe inconnue'
            ]);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'client_secret' => $setupIntent['data']['client_secret'] ?? null,
            'setup_intent_id' => $setupIntent['data']['id'] ?? null,
            'is_subscription' => true,
            'amount' => $amount,
            'interval' => $recurringInterval,
            'interval_count' => $recurringIntervalCount,
            'frequency_label' => $frequencyLabel
        ]);
        exit;
    }

    // Pour les paiements uniques (achats ou dons ponctuels)
    $shippingFee = $stripe->getDefaultShippingFee();
    if ($from === 'achats' && $amount > 60.0) {
        $shippingFee = 0.0;
    }

    if ($from === 'don') {
        $shippingFee = 0.0;
    }

    if ($from === 'achats') {
        $metadata['type'] = 'achat';
        if (!empty($articleId)) {
            $projectResult = $back4app->getById('Project', $articleId);
            if ($projectResult['success'] ?? false) {
                $project = $projectResult['data'] ?? [];
                if (!empty($project)) {
                    $amount = isset($project['prix']) ? (float) $project['prix'] : $amount;
                    $metadata['project_title'] = $project['titre'] ?? '';
                    $metadata['project_id'] = $articleId;
                }
            }
        }
    } else {
        $metadata['type'] = 'don_ponctuel';
    }

    // Ajouter l'email dans les métadonnées pour le webhook
    if ($customerEmail) {
        $metadata['email'] = $customerEmail;
    }

    $paymentIntent = $stripe->createPaymentIntent([
        'amount' => $amount,
        'customer_email' => $customerEmail,
        'metadata' => array_filter($metadata, fn($value) => $value !== null && $value !== ''),
        'shipping_fee' => $shippingFee,
    ]);

    if (!$paymentIntent['success']) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $paymentIntent['error'] ?? 'Erreur Stripe inconnue'
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'client_secret' => $paymentIntent['data']['client_secret'] ?? null,
        'payment_intent_id' => $paymentIntent['data']['id'] ?? null,
        'is_subscription' => false
    ]);
    exit;
}

// Création d'une Subscription après confirmation du Setup Intent
if ($path === '/create-subscription' && $method === 'POST') {
    if ($stripe === null) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Stripe n\'est pas configuré.'
        ]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $paymentMethodId = $input['payment_method_id'] ?? '';
    $amount = isset($input['amount']) ? (float) $input['amount'] : 0;
    $interval = $input['interval'] ?? 'month';
    $intervalCount = isset($input['interval_count']) ? (int) $input['interval_count'] : 1;
    $customerEmail = $input['email'] ?? null;
    $frequencyLabel = $input['frequency_label'] ?? '';

    if (empty($paymentMethodId) || $amount <= 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Paramètres manquants ou invalides'
        ]);
        exit;
    }

    $metadata = [
        'nom' => $input['nom'] ?? '',
        'prenom' => $input['prenom'] ?? '',
        'adresse' => $input['adresse'] ?? '',
        'code_postal' => $input['code_postal'] ?? '',
        'ville' => $input['ville'] ?? '',
        'type' => 'don_regulier',
        'frequency' => $frequencyLabel,
        'frequency_interval' => $interval,
        'frequency_interval_count' => $intervalCount,
        'frequency_label' => $frequencyLabel,
        'source' => 'don',
    ];

    $subscription = $stripe->createSubscription([
        'amount' => $amount,
        'payment_method_id' => $paymentMethodId,
        'customer_email' => $customerEmail,
        'interval' => $interval,
        'interval_count' => $intervalCount,
        'metadata' => array_filter($metadata, fn($value) => $value !== null && $value !== ''),
        'product_name' => sprintf('Don %s - quantanous', $frequencyLabel),
    ]);

    if (!$subscription['success']) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $subscription['error'] ?? 'Erreur lors de la création de l\'abonnement'
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'subscription' => $subscription['data']
    ]);
    exit;
}

// Création d'une session Stripe Checkout
if ($path === '/create-checkout-session' && $method === 'POST') {
    if ($stripe === null) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Stripe n\'est pas configuré. Vérifiez stripe-config.php.'
        ]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $amount = isset($input['montant']) ? (float) $input['montant'] : 0;
    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Montant invalide'
        ]);
        exit;
    }

    $from = $input['from'] ?? '';
    $frequency = $input['frequency'] ?? '';
    $articleId = $input['article_id'] ?? '';
    $customerEmail = $input['email'] ?? null;
    $frequencyInterval = $input['frequency_interval'] ?? '';
    $frequencyIntervalCount = isset($input['frequency_interval_count']) ? (int) $input['frequency_interval_count'] : null;
    $frequencyLabel = $input['frequency_label'] ?? '';

    $mode = 'payment';
    $shippingFee = $stripe->getDefaultShippingFee();
    // Frais de livraison gratuits si montant > 60€ pour les achats
    if ($from === 'achats' && $amount > 60.0) {
        $shippingFee = 0.0;
    }
    $productName = 'Paiement quantanous';
    $description = sprintf('Paiement de %.2f €', $amount);

    $metadata = [
        'nom' => $input['nom'] ?? '',
        'prenom' => $input['prenom'] ?? '',
        'adresse' => $input['adresse'] ?? '',
        'code_postal' => $input['code_postal'] ?? '',
        'ville' => $input['ville'] ?? '',
        'article_id' => $articleId,
        'frequency' => $frequency,
        'source' => $from,
    ];

    $recurringInterval = null;
    $recurringIntervalCount = null;

    if ($from === 'don') {
        $shippingFee = 0.0;

        if ($frequency === 'regulier') {
            $intervalMapByLabel = [
                'mensuel' => ['month', 1],
                'trimestriel' => ['month', 3],
                'semestriel' => ['month', 6],
                'annuel' => ['year', 1],
            ];

            $mode = 'subscription';

            $normalizedLabel = strtolower($frequencyLabel);
            if (isset($intervalMapByLabel[$normalizedLabel])) {
                [$recurringInterval, $recurringIntervalCount] = $intervalMapByLabel[$normalizedLabel];
            }

            $allowedIntervals = ['day', 'week', 'month', 'year'];
            if (!in_array($frequencyInterval, $allowedIntervals, true)) {
                $frequencyInterval = '';
            }

            if ($frequencyInterval && $frequencyIntervalCount) {
                $recurringInterval = $frequencyInterval;
                $recurringIntervalCount = max(1, $frequencyIntervalCount);
            }

            if (!$recurringInterval) {
                // Valeurs par défaut si rien de valide fourni
                $recurringInterval = 'month';
                $recurringIntervalCount = 1;
                $normalizedLabel = 'mensuel';
            }

            if (empty($frequencyLabel)) {
                $labelByInterval = [
                    'day' => 'quotidien',
                    'week' => 'hebdomadaire',
                    'month' => match ($recurringIntervalCount) {
                        3 => 'trimestriel',
                        6 => 'semestriel',
                        default => 'mensuel',
                    },
                    'year' => 'annuel',
                ];
                $frequencyLabel = $labelByInterval[$recurringInterval] ?? 'mensuel';
                $normalizedLabel = strtolower($frequencyLabel);
            }

            $mode = 'subscription';
            $productName = sprintf('Don %s - quantanous', $frequencyLabel);
            $description = sprintf('Don %s de %.2f €', $frequencyLabel, $amount);
            $metadata['type'] = 'don_regulier';
            $metadata['frequency'] = $frequencyLabel;
            $metadata['frequency_interval'] = $recurringInterval;
            $metadata['frequency_interval_count'] = $recurringIntervalCount;
            $metadata['frequency_label'] = $frequencyLabel;
        } else {
            $productName = 'Don ponctuel - quantanous';
            $description = sprintf('Don ponctuel de %.2f €', $amount);
            $metadata['type'] = 'don_ponctuel';
        }
    } else {
        $metadata['type'] = 'achat';
        $productName = 'Achat - quantanous';
        $description = sprintf('Achat de %.2f €', $amount);

        if (!empty($articleId)) {
            $projectResult = $back4app->getById('Project', $articleId);
            if ($projectResult['success'] ?? false) {
                $project = $projectResult['data'] ?? [];
                if (!empty($project)) {
                    $amount = isset($project['prix']) ? (float) $project['prix'] : $amount;
                    $productName = $project['titre'] ?? $productName;
                    $description = ($project['description'] ?? '') ?: $description;
                    $metadata['project_title'] = $project['titre'] ?? '';
                    $metadata['project_id'] = $articleId; // Ajouter project_id pour la gestion du stock
                }
            }
        }
    }

    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Montant invalide après validation serveur'
        ]);
        exit;
    }

    $session = $stripe->createCheckoutSession([
        'amount' => $amount,
        'product_name' => $productName,
        'description' => $description,
        'customer_email' => $customerEmail,
        'metadata' => array_filter($metadata, fn($value) => $value !== null && $value !== ''),
        'mode' => $mode,
        'shipping_fee' => $shippingFee,
        'recurring_interval' => $mode === 'subscription' ? $recurringInterval : null,
        'recurring_interval_count' => $mode === 'subscription' ? $recurringIntervalCount : null,
    ]);

    if (!$session['success']) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $session['error'] ?? 'Erreur Stripe inconnue'
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'checkout_url' => $session['data']['url'] ?? null
    ]);
    exit;
}

// Webhook Stripe
if ($path === '/stripe-webhook' && $method === 'POST') {
    if ($stripe === null) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Stripe n\'est pas configuré.'
        ]);
        exit;
    }

    $payload = file_get_contents('php://input');
    $signatureHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

    try {
        $isValid = $stripe->verifyWebhookSignature($payload, $signatureHeader);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Erreur vérification webhook: ' . $e->getMessage()
        ]);
        exit;
    }

    if (!$isValid) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Signature Stripe invalide'
        ]);
        exit;
    }

    $event = json_decode($payload, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Payload JSON invalide'
        ]);
        exit;
    }

    $sendCheckoutSessionEmail = function (array $session) use ($stripe, $emailHelper): bool {
        if (!$emailHelper) {
            return false;
        }

        if (($session['payment_status'] ?? '') !== 'paid') {
            return false;
        }

        $sessionId = $session['id'] ?? '';
        $lineItems = [];
        if ($sessionId) {
            $sessionDetails = $stripe->retrieveCheckoutSession($sessionId, true);
            if (($sessionDetails['success'] ?? false) && isset($sessionDetails['data']['line_items']['data'])) {
                $lineItems = $sessionDetails['data']['line_items']['data'];
            }
        }

        $metadata = $session['metadata'] ?? [];
        $type = $metadata['type'] ?? (($session['mode'] ?? '') === 'subscription' ? 'don_regulier' : 'don_ponctuel');
        $amount = isset($session['amount_total']) ? $session['amount_total'] / 100 : 0;
        $currency = strtoupper($session['currency'] ?? $stripe->getCurrency());

        $customerDetails = $session['customer_details'] ?? [];
        $customerEmail = $customerDetails['email'] ?? $session['customer_email'] ?? ($metadata['email'] ?? '');
        if (empty($customerEmail)) {
            return false;
        }

        $customerName = $customerDetails['name'] ?? trim(($metadata['prenom'] ?? '') . ' ' . ($metadata['nom'] ?? ''));

        return $emailHelper->sendReceipt([
            'to' => $customerEmail,
            'type' => $type,
            'amount' => $amount,
            'currency' => $currency,
            'frequency' => $metadata['frequency'] ?? ($metadata['frequency_label'] ?? ''),
            'metadata' => $metadata,
            'line_items' => $lineItems,
            'customer' => ['name' => $customerName, 'email' => $customerEmail],
        ]);
    };

    // Fonction pour gérer le stock et envoyer les alertes
    $handleStockAndAlerts = function (array $session) use ($back4app, $emailHelper): void {
        $metadata = $session['metadata'] ?? [];
        $type = $metadata['type'] ?? '';
        
        // Ne traiter que les achats
        if ($type !== 'achat') {
            return;
        }

        $projectId = $metadata['project_id'] ?? '';
        if (empty($projectId)) {
            return;
        }

        // Récupérer le projet depuis Back4app
        $projectResult = $back4app->getById('Project', $projectId);
        if (!($projectResult['success'] ?? false) || empty($projectResult['data'])) {
            error_log('Stock alert: Projet non trouvé - ID: ' . $projectId);
            return;
        }

        $project = $projectResult['data'];
        $currentQuantity = isset($project['quantite']) ? max(0, intval($project['quantite'])) : 0;
        $emailAlerte = $project['email_alerte'] ?? null;
        $projectTitle = $project['titre'] ?? 'Article';

        // Décrémenter la quantité de 1
        $newQuantity = max(0, $currentQuantity - 1);

        // Mettre à jour le stock dans Back4app
        $updateResult = $back4app->update('Project', $projectId, [
            'quantite' => $newQuantity,
            'updated_at' => date('c')
        ]);

        if (!($updateResult['success'] ?? false)) {
            error_log('Stock alert: Erreur lors de la mise à jour du stock - ID: ' . $projectId);
            return;
        }

        error_log("Stock alert: Stock mis à jour pour '{$projectTitle}' - Ancien: {$currentQuantity}, Nouveau: {$newQuantity}");

        // Vérifier si le stock est en dessous de 5 et envoyer une alerte
        if ($newQuantity < 5 && !empty($emailAlerte) && filter_var($emailAlerte, FILTER_VALIDATE_EMAIL)) {
            $subject = "⚠️ Alerte de stock faible - {$projectTitle}";
            $htmlBody = "
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .alert-box { background-color: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 20px; margin: 20px 0; }
                        .alert-title { color: #856404; font-size: 20px; font-weight: bold; margin-bottom: 15px; }
                        .info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                        .stock-value { font-size: 24px; font-weight: bold; color: #dc3545; }
                        .action { background-color: #28a745; color: white; padding: 10px 20px; border-radius: 5px; margin-top: 20px; display: inline-block; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='alert-box'>
                            <div class='alert-title'>⚠️ Alerte de stock faible</div>
                            <p>Le stock de l'article suivant est maintenant en dessous de 5 unités :</p>
                            <div class='info'>
                                <strong>Article :</strong> {$projectTitle}<br>
                                <strong>Stock actuel :</strong> <span class='stock-value'>{$newQuantity}</span> unité(s)
                            </div>
                            <p><strong>Action requise :</strong> Veuillez procéder au réapprovisionnement de cet article.</p>
                            <p>Cet email est un rappel automatique. Vous recevrez un nouveau rappel lors de chaque achat tant que le stock reste en dessous de 5 unités.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            $textBody = "Alerte de stock faible\n\nArticle: {$projectTitle}\nStock actuel: {$newQuantity} unité(s)\n\nVeuillez procéder au réapprovisionnement.";

            $emailSent = $emailHelper->sendEmail($emailAlerte, $subject, $htmlBody, $textBody);
            
            if ($emailSent) {
                error_log("Stock alert: Email d'alerte envoyé à {$emailAlerte} pour '{$projectTitle}' (stock: {$newQuantity})");
            } else {
                error_log("Stock alert: Échec de l'envoi de l'email d'alerte à {$emailAlerte}");
            }
        }
    };

    $sendInvoiceEmail = function (array $invoice) use ($stripe, $emailHelper): bool {
        if (!$emailHelper) {
            error_log('Webhook Stripe: EmailHelper non disponible');
            return false;
        }

        if (($invoice['paid'] ?? false) !== true) {
            error_log('Webhook Stripe: Facture non payée, email non envoyé');
            return false;
        }

        $metadata = $invoice['metadata'] ?? [];
        if (empty($metadata) && !empty($invoice['subscription'])) {
            $subscription = $stripe->retrieveSubscription($invoice['subscription']);
            if (($subscription['success'] ?? false) && isset($subscription['data']['metadata'])) {
                $metadata = $subscription['data']['metadata'];
            }
        }

        // Récupérer les métadonnées depuis les line_items si elles ne sont pas dans invoice.metadata
        if (empty($metadata) && !empty($invoice['lines']['data'][0]['metadata'])) {
            $metadata = $invoice['lines']['data'][0]['metadata'];
        }

        $type = $metadata['type'] ?? 'don_regulier';
        $amount = isset($invoice['amount_paid']) ? $invoice['amount_paid'] / 100 : 0;
        $currency = strtoupper($invoice['currency'] ?? $stripe->getCurrency());
        $lineItems = [];

        foreach ($invoice['lines']['data'] ?? [] as $line) {
            $lineItems[] = [
                'description' => $line['description'] ?? ($line['plan']['nickname'] ?? 'Abonnement'),
                'quantity' => $line['quantity'] ?? 1,
                'amount_total' => $line['amount'] ?? ($line['amount_total'] ?? 0),
            ];
        }

        $periodStart = $invoice['lines']['data'][0]['period']['start'] ?? null;
        $periodEnd = $invoice['lines']['data'][0]['period']['end'] ?? null;

        $customerEmail = $invoice['customer_email'] ?? ($invoice['customer_details']['email'] ?? '');
        if (empty($customerEmail)) {
            error_log('Webhook Stripe: Email client manquant dans la facture');
            return false;
        }

        $customerName = $invoice['customer_name'] ?? trim(($metadata['prenom'] ?? '') . ' ' . ($metadata['nom'] ?? ''));

        error_log('Webhook Stripe: Tentative d\'envoi d\'email à ' . $customerEmail . ' pour facture ' . ($invoice['id'] ?? 'inconnue'));

        $result = $emailHelper->sendReceipt([
            'to' => $customerEmail,
            'type' => $type,
            'amount' => $amount,
            'currency' => $currency,
            'frequency' => $metadata['frequency'] ?? ($metadata['frequency_label'] ?? 'mensuel'),
            'metadata' => $metadata,
            'line_items' => $lineItems,
            'customer' => ['name' => $customerName, 'email' => $customerEmail],
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);

        if (!$result) {
            error_log('Webhook Stripe: Échec de l\'envoi de l\'email à ' . $customerEmail);
        } else {
            error_log('Webhook Stripe: Email envoyé avec succès à ' . $customerEmail);
        }

        return $result;
    };

    // Fonction pour envoyer l'email à partir d'un Payment Intent
    $sendPaymentIntentEmail = function (array $paymentIntent) use ($emailHelper, $back4app): bool {
        if (!$emailHelper) {
            error_log('Webhook Stripe: EmailHelper non disponible pour Payment Intent');
            return false;
        }

        if (($paymentIntent['status'] ?? '') !== 'succeeded') {
            error_log('Webhook Stripe: Payment Intent non réussi, email non envoyé');
            return false;
        }

        $metadata = $paymentIntent['metadata'] ?? [];
        $type = $metadata['type'] ?? 'paiement';
        $amount = isset($paymentIntent['amount']) ? $paymentIntent['amount'] / 100 : 0;
        $currency = strtoupper($paymentIntent['currency'] ?? 'eur');
        
        // Récupérer l'email depuis receipt_email ou metadata
        $customerEmail = $paymentIntent['receipt_email'] ?? ($metadata['email'] ?? '');
        if (empty($customerEmail)) {
            error_log('Webhook Stripe: Email client manquant dans le Payment Intent');
            return false;
        }

        $customerName = trim(($metadata['prenom'] ?? '') . ' ' . ($metadata['nom'] ?? ''));

        error_log('Webhook Stripe: Tentative d\'envoi d\'email à ' . $customerEmail . ' pour Payment Intent ' . ($paymentIntent['id'] ?? 'inconnu'));

        $result = $emailHelper->sendReceipt([
            'to' => $customerEmail,
            'type' => $type,
            'amount' => $amount,
            'currency' => $currency,
            'frequency' => $metadata['frequency'] ?? ($metadata['frequency_label'] ?? ''),
            'metadata' => $metadata,
            'line_items' => [],
            'customer' => ['name' => $customerName, 'email' => $customerEmail],
        ]);

        if (!$result) {
            error_log('Webhook Stripe: Échec de l\'envoi de l\'email à ' . $customerEmail);
        } else {
            error_log('Webhook Stripe: Email envoyé avec succès à ' . $customerEmail);
        }

        return $result;
    };

    // Fonction pour gérer le stock à partir d'un Payment Intent
    $handleStockAndAlertsFromPaymentIntent = function (array $paymentIntent) use ($back4app, $emailHelper): void {
        $metadata = $paymentIntent['metadata'] ?? [];
        $type = $metadata['type'] ?? '';
        
        // Ne traiter que les achats
        if ($type !== 'achat') {
            return;
        }

        $projectId = $metadata['project_id'] ?? '';
        if (empty($projectId)) {
            return;
        }

        // Récupérer le projet depuis Back4app
        $projectResult = $back4app->getById('Project', $projectId);
        if (!($projectResult['success'] ?? false) || empty($projectResult['data'])) {
            error_log('Stock alert: Projet non trouvé - ID: ' . $projectId);
            return;
        }

        $project = $projectResult['data'];
        $currentQuantity = isset($project['quantite']) ? max(0, intval($project['quantite'])) : 0;
        $emailAlerte = $project['email_alerte'] ?? null;
        $projectTitle = $project['titre'] ?? 'Article';

        // Décrémenter la quantité de 1
        $newQuantity = max(0, $currentQuantity - 1);

        // Mettre à jour le stock dans Back4app
        $updateResult = $back4app->update('Project', $projectId, [
            'quantite' => $newQuantity,
            'updated_at' => date('c')
        ]);

        if (!($updateResult['success'] ?? false)) {
            error_log('Stock alert: Erreur lors de la mise à jour du stock - ID: ' . $projectId);
            return;
        }

        error_log("Stock alert: Stock mis à jour pour '{$projectTitle}' - Ancien: {$currentQuantity}, Nouveau: {$newQuantity}");

        // Vérifier si le stock est en dessous de 5 et envoyer une alerte
        if ($newQuantity < 5 && !empty($emailAlerte) && filter_var($emailAlerte, FILTER_VALIDATE_EMAIL)) {
            $subject = "⚠️ Alerte de stock faible - {$projectTitle}";
            $htmlBody = "
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .alert-box { background-color: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 20px; margin: 20px 0; }
                        .alert-title { color: #856404; font-size: 20px; font-weight: bold; margin-bottom: 15px; }
                        .info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                        .stock-value { font-size: 24px; font-weight: bold; color: #dc3545; }
                        .action { background-color: #28a745; color: white; padding: 10px 20px; border-radius: 5px; margin-top: 20px; display: inline-block; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='alert-box'>
                            <div class='alert-title'>⚠️ Alerte de stock faible</div>
                            <p>Le stock de l'article suivant est maintenant en dessous de 5 unités :</p>
                            <div class='info'>
                                <strong>Article :</strong> {$projectTitle}<br>
                                <strong>Stock actuel :</strong> <span class='stock-value'>{$newQuantity}</span> unité(s)
                            </div>
                            <p><strong>Action requise :</strong> Veuillez procéder au réapprovisionnement de cet article.</p>
                            <p>Cet email est un rappel automatique. Vous recevrez un nouveau rappel lors de chaque achat tant que le stock reste en dessous de 5 unités.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            $textBody = "Alerte de stock faible\n\nArticle: {$projectTitle}\nStock actuel: {$newQuantity} unité(s)\n\nVeuillez procéder au réapprovisionnement.";

            $emailSent = $emailHelper->sendEmail($emailAlerte, $subject, $htmlBody, $textBody);
            
            if ($emailSent) {
                error_log("Stock alert: Email d'alerte envoyé à {$emailAlerte} pour '{$projectTitle}' (stock: {$newQuantity})");
            } else {
                error_log("Stock alert: Échec de l'envoi de l'email d'alerte à {$emailAlerte}");
            }
        }
    };

    $eventType = $event['type'] ?? '';
    $emailSent = false;
    $message = 'Événement traité';

    switch ($eventType) {
        case 'checkout.session.completed':
            $session = $event['data']['object'] ?? [];
            $emailSent = $sendCheckoutSessionEmail($session);
            // Gérer le stock et les alertes pour les achats
            $handleStockAndAlerts($session);
            $message = 'Session checkout traitée';
            break;
        case 'invoice.payment_succeeded':
            $invoice = $event['data']['object'] ?? [];
            $emailSent = $sendInvoiceEmail($invoice);
            $message = 'Facture traitée';
            break;
        case 'payment_intent.succeeded':
            $paymentIntent = $event['data']['object'] ?? [];
            $emailSent = $sendPaymentIntentEmail($paymentIntent);
            // Gérer le stock et les alertes pour les achats
            $handleStockAndAlertsFromPaymentIntent($paymentIntent);
            $message = 'Payment Intent traité';
            break;
        default:
            $message = 'Événement ignoré: ' . $eventType;
    }

    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'email_sent' => $emailSent
    ]);
    exit;
}

// Route POST exemple
if ($path === '/data' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Données reçues',
        'received_data' => $input,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Route pour les dons
if ($path === '/don' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validation des données
    if (!isset($input['amount']) && !isset($input['montant'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Montant manquant'
        ]);
        exit;
    }
    
    $montant = $input['amount'] ?? $input['montant'];
    $type = $input['type'] ?? 'don';
    $frequency = $input['frequency'] ?? null;
    $article_id = $input['article_id'] ?? null;
    $titre = $input['titre'] ?? null;
    
    // Ici, vous pouvez sauvegarder dans une base de données
    // Pour l'instant, on simule juste une réponse de succès
    
    // Exemple de sauvegarde (à adapter selon vos besoins)
    $donData = [
        'id' => uniqid(),
        'type' => $type,
        'montant' => floatval($montant),
        'frequency' => $frequency,
        'article_id' => $article_id,
        'titre' => $titre,
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // Logique de sauvegarde à implémenter ici
    // Par exemple : sauvegarder dans un fichier JSON ou base de données
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Don enregistré avec succès',
        'don' => $donData
    ]);
    exit;
}

// Route pour sauvegarder la présentation
if ($path === '/save-presentation' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['content'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Contenu manquant'
        ]);
        exit;
    }
    
    $type = $input['type'] ?? 'presentation';
    
    // Sauvegarder dans Back4app
    $data = [
        'type' => $type,
        'content' => $input['content'],
        'updated_at' => date('c') // ISO 8601 format
    ];
    
    // Chercher si un document existe déjà pour ce type
    $existing = $back4app->get('Content', ['type' => $type], 1);
    
    if ($existing['success'] && isset($existing['data']['results']) && count($existing['data']['results']) > 0) {
        // Mettre à jour l'existant
        $objectId = $existing['data']['results'][0]['objectId'];
        $result = $back4app->update('Content', $objectId, $data);
    } else {
        // Créer un nouveau
        $result = $back4app->create('Content', $data);
    }
    
    if ($result['success']) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Contenu sauvegardé avec succès',
            'type' => $type
        ]);
    } else {
        $errorMessage = $result['data']['error'] ?? $result['error'] ?? 'Erreur lors de la sauvegarde';
        $errorCode = $result['data']['code'] ?? ($result['http_code'] ?? 0);
        error_log('Back4App save error (' . $type . '): ' . json_encode($result, JSON_PRETTY_PRINT));

        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $errorMessage,
            'code' => $errorCode,
            'hint' => 'Vérifiez vos classes Back4app (Content) et vos clés API.'
        ]);
    }
    exit;
}

// Route pour récupérer le contenu
if ($path === '/get-content' && $method === 'GET') {
    $type = $_GET['type'] ?? 'presentation';
    
    $result = $back4app->get('Content', ['type' => $type], 1);
    
    if ($result['success'] && isset($result['data']['results']) && count($result['data']['results']) > 0) {
        echo json_encode([
            'status' => 'success',
            'content' => $result['data']['results'][0]['content'] ?? ''
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'content' => ''
        ]);
    }
    exit;
}

// Route pour le paiement
if ($path === '/paiement' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validation des données essentielles
    $requiredFields = ['nom', 'prenom', 'adresse', 'code_postal', 'ville', 'email', 'carte_numero', 'montant'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => "Le champ '{$field}' est requis"
            ]);
            exit;
        }
    }
    
    // Validation de l'email
    if ($input['email'] !== ($input['email_confirm'] ?? '')) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Les adresses email ne correspondent pas'
        ]);
        exit;
    }
    
    // Ici, vous intégreriez un vrai système de paiement (Stripe, PayPal, etc.)
    // Pour l'instant, on simule juste un paiement réussi
    
    $paiementData = [
        'id' => uniqid(),
        'nom' => $input['nom'],
        'prenom' => $input['prenom'],
        'adresse' => $input['adresse'],
        'code_postal' => $input['code_postal'],
        'ville' => $input['ville'],
        'email' => $input['email'],
        'montant' => floatval($input['montant']),
        'article_id' => $input['article_id'] ?? null,
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'statut' => 'traité'
    ];
    
    // Sauvegarder les données de paiement (dans un vrai système, utiliser une base de données)
    if (!is_dir('data')) {
        mkdir('data', 0755, true);
    }
    
    $paiementsFile = 'data/paiements.json';
    $paiements = [];
    if (file_exists($paiementsFile)) {
        $paiements = json_decode(file_get_contents($paiementsFile), true) ?: [];
    }
    $paiements[] = $paiementData;
    file_put_contents($paiementsFile, json_encode($paiements, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Paiement effectué avec succès',
        'paiement' => [
            'id' => $paiementData['id'],
            'montant' => $paiementData['montant']
        ]
    ]);
    exit;
}

// Route pour ajouter un projet
if ($path === '/add-project' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validation
    $requiredFields = ['titre', 'description', 'prix', 'quantite', 'email_alerte'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || (is_string($input[$field]) && trim($input[$field]) === '')) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => "Le champ '{$field}' est requis."
            ]);
            exit;
        }
    }

    // Validation spécifique pour quantite (doit être un nombre >= 0)
    $quantite = isset($input['quantite']) ? intval($input['quantite']) : null;
    if ($quantite === null || $quantite < 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'La quantité doit être un nombre entier supérieur ou égal à 0.'
        ]);
        exit;
    }

    // Validation spécifique pour email_alerte (doit être un email valide)
    $emailAlerte = isset($input['email_alerte']) ? trim($input['email_alerte']) : '';
    if (empty($emailAlerte) || !filter_var($emailAlerte, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Un email valide est requis pour les alertes de stock.'
        ]);
        exit;
    }

    $titleSlug = normalize_project_title($input['titre']);

    $images = [];
    if (isset($input['images']) && is_array($input['images'])) {
        foreach ($input['images'] as $img) {
            if (is_string($img) && $img !== '') {
                $images[] = $img;
            }
        }
    }

    $primaryImage = $images[0] ?? ($input['image'] ?? '');
    if ($primaryImage !== '' && empty($images)) {
        $images[] = $primaryImage;
    }

    // Préparer les données du projet
    $projectData = [
        'titre' => $input['titre'],
        'description' => $input['description'],
        'prix' => floatval($input['prix']),
        'tva_incluse' => $input['tva_incluse'] ?? false,
        'quantite' => $quantite,
        'email_alerte' => $emailAlerte,
        'image' => $primaryImage,
        'images' => $images,
        'titre_slug' => $titleSlug,
        'created_at' => $input['timestamp'] ?? date('c'),
        'updated_at' => date('c')
    ];

    $isUpdate = false;
    $objectId = null;

    if (isset($input['id']) && is_string($input['id']) && preg_match('/^[A-Za-z0-9]{10}$/', $input['id'])) {
        $isUpdate = true;
        $objectId = $input['id'];
    }

    // Vérification unicité du titre
    $duplicates = $back4app->get('Project', ['titre_slug' => $titleSlug], 1);
    if (!($duplicates['success'] ?? false) || empty($duplicates['data']['results'])) {
        $duplicates = $back4app->get('Project', ['titre' => $input['titre']], 1);
    }
    if (($duplicates['success'] ?? false) && !empty($duplicates['data']['results'])) {
        $foundId = $duplicates['data']['results'][0]['objectId'] ?? null;
        if (!$isUpdate || ($foundId && $foundId !== $objectId)) {
            http_response_code(409);
            echo json_encode([
                'status' => 'error',
                'message' => 'Un projet avec ce titre existe déjà.'
            ]);
            exit;
        }
    }

    if ($isUpdate) {
        unset($projectData['created_at']);
    }

    if ($isUpdate) {
        $result = $back4app->update('Project', $objectId, $projectData);
    } else {
        $result = $back4app->create('Project', $projectData);
    }

    if ($result['success'] ?? false) {
        $project = $projectData;
        if ($isUpdate) {
            $project['id'] = $objectId;
        } elseif (isset($result['data']['objectId'])) {
            $project['id'] = $result['data']['objectId'];
        }
        $project['images'] = $images;
        
        echo json_encode([
            'status' => 'success',
            'message' => $isUpdate ? 'Projet mis à jour avec succès' : 'Projet ajouté avec succès',
            'project' => $project
        ]);
    } else {
        $errorMessage = $result['data']['error'] ?? $result['error'] ?? 'Erreur lors de la sauvegarde';
        error_log('Back4App add-project error: ' . json_encode($result, JSON_PRETTY_PRINT));
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Erreur lors de la sauvegarde: ' . $errorMessage
        ]);
    }
    exit;
}

// Route pour récupérer un projet spécifique
if ($path === '/get-project' && $method === 'GET') {
    $projectId = $_GET['id'] ?? '';

    if (empty($projectId)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'ID du projet requis'
        ]);
        exit;
    }

    $result = $back4app->getById('Project', $projectId);

    if (($result['success'] ?? false) && isset($result['data'])) {
        $item = $result['data'];
        $images = $item['images'] ?? [];
        if (!is_array($images)) {
            $images = [];
        }
        $primaryImage = $images[0] ?? ($item['image'] ?? '');
        if (empty($images) && !empty($primaryImage)) {
            $images[] = $primaryImage;
        }

        echo json_encode([
            'status' => 'success',
            'project' => [
                'id' => $item['objectId'] ?? $projectId,
                'titre' => $item['titre'] ?? '',
                'description' => $item['description'] ?? '',
                'prix' => $item['prix'] ?? 0,
                'tva_incluse' => $item['tva_incluse'] ?? false,
                'quantite' => isset($item['quantite']) ? intval($item['quantite']) : 0,
                'email_alerte' => $item['email_alerte'] ?? null,
                'image' => $primaryImage,
                'images' => $images,
                'created_at' => $item['created_at'] ?? null,
                'updated_at' => $item['updated_at'] ?? null,
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Projet introuvable'
        ]);
    }
    exit;
}

// Route pour récupérer les projets
if ($path === '/get-projects' && $method === 'GET') {
    $result = $back4app->get('Project', null, 100, '-createdAt');
    
    if ($result['success'] && isset($result['data']['results'])) {
        $projects = array_map(function($item) {
            $images = $item['images'] ?? [];
            if (!is_array($images)) {
                $images = [];
            }
            $primaryImage = $images[0] ?? ($item['image'] ?? '');
            if (empty($images) && !empty($primaryImage)) {
                $images[] = $primaryImage;
            }

            return [
                'id' => $item['objectId'],
                'titre' => $item['titre'] ?? '',
                'description' => $item['description'] ?? '',
                'prix' => $item['prix'] ?? 0,
                'tva_incluse' => $item['tva_incluse'] ?? false,
                'quantite' => isset($item['quantite']) ? intval($item['quantite']) : 0,
                'email_alerte' => $item['email_alerte'] ?? null,
                'image' => $primaryImage,
                'images' => $images,
                'timestamp' => $item['created_at'] ?? date('Y-m-d H:i:s')
            ];
        }, $result['data']['results']);
        
        echo json_encode([
            'status' => 'success',
            'projects' => $projects
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'projects' => []
        ]);
    }
    exit;
}

// Route pour compléter un paiement (décrémenter le stock et envoyer l'email)
if ($path === '/complete-payment' && $method === 'POST') {
    error_log('📥 Route /complete-payment appelée');
    $input = json_decode(file_get_contents('php://input'), true);
    error_log('📋 Données reçues: ' . json_encode($input));
    
    $articleId = $input['article_id'] ?? '';
    $customerEmail = $input['email'] ?? '';
    $customerName = trim(($input['prenom'] ?? '') . ' ' . ($input['nom'] ?? ''));
    $amount = isset($input['montant']) ? (float) $input['montant'] : 0;
    $type = $input['type'] ?? 'achat'; // 'achat' ou 'don_ponctuel'
    
    error_log("📊 Type: {$type}, Montant: {$amount}, Email: {$customerEmail}, Article ID: {$articleId}");
    
    // Décrémenter le stock si c'est un achat
    $stockUpdated = false;
    $stockResult = null;
    if ($type === 'achat' && !empty($articleId)) {
        // Récupérer le projet depuis Back4app
        $projectResult = $back4app->getById('Project', $articleId);
        if (($projectResult['success'] ?? false) && !empty($projectResult['data'])) {
            $project = $projectResult['data'];
            $currentQuantity = isset($project['quantite']) ? max(0, intval($project['quantite'])) : 0;
            $emailAlerte = $project['email_alerte'] ?? null;
            $projectTitle = $project['titre'] ?? 'Article';
            
            // Décrémenter la quantité de 1
            $newQuantity = max(0, $currentQuantity - 1);
            
            // Mettre à jour le stock dans Back4app
            $updateResult = $back4app->update('Project', $articleId, [
                'quantite' => $newQuantity,
                'updated_at' => date('c')
            ]);
            
            if (($updateResult['success'] ?? false)) {
                $stockUpdated = true;
                $stockResult = ['old' => $currentQuantity, 'new' => $newQuantity];
                error_log("Stock: Stock mis à jour pour '{$projectTitle}' - Ancien: {$currentQuantity}, Nouveau: {$newQuantity}");
                
                // Envoyer une alerte si le stock est en dessous de 5
                if ($newQuantity < 5 && !empty($emailAlerte) && filter_var($emailAlerte, FILTER_VALIDATE_EMAIL) && $emailHelper) {
                    $subject = "⚠️ Alerte de stock faible - {$projectTitle}";
                    $htmlBody = "
                        <html>
                        <head>
                            <meta charset='UTF-8'>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                .alert-box { background-color: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 20px; margin: 20px 0; }
                                .alert-title { color: #856404; font-size: 20px; font-weight: bold; margin-bottom: 15px; }
                                .info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                                .stock-value { font-size: 24px; font-weight: bold; color: #dc3545; }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <div class='alert-box'>
                                    <div class='alert-title'>⚠️ Alerte de stock faible</div>
                                    <p>Le stock de l'article suivant est maintenant en dessous de 5 unités :</p>
                                    <div class='info'>
                                        <strong>Article :</strong> {$projectTitle}<br>
                                        <strong>Stock actuel :</strong> <span class='stock-value'>{$newQuantity}</span> unité(s)
                                    </div>
                                    <p><strong>Action requise :</strong> Veuillez procéder au réapprovisionnement de cet article.</p>
                                </div>
                            </div>
                        </body>
                        </html>
                    ";
                    $textBody = "Alerte de stock faible\n\nArticle: {$projectTitle}\nStock actuel: {$newQuantity} unité(s)\n\nVeuillez procéder au réapprovisionnement.";
                    $emailHelper->sendEmail($emailAlerte, $subject, $htmlBody, $textBody);
                }
            }
        }
    }
    
    // Envoyer l'email de reçu si un email est fourni
    $emailSent = false;
    if (!empty($customerEmail) && $emailHelper && $amount > 0) {
        $metadata = [
            'nom' => $input['nom'] ?? '',
            'prenom' => $input['prenom'] ?? '',
            'adresse' => $input['adresse'] ?? '',
            'code_postal' => $input['code_postal'] ?? '',
            'ville' => $input['ville'] ?? '',
        ];
        if ($type === 'achat' && !empty($articleId)) {
            $metadata['article_id'] = $articleId;
            $metadata['project_id'] = $articleId;
        }
        
        $emailSent = $emailHelper->sendReceipt([
            'to' => $customerEmail,
            'type' => $type,
            'amount' => $amount,
            'currency' => 'EUR',
            'metadata' => $metadata,
            'line_items' => [],
            'customer' => ['name' => $customerName, 'email' => $customerEmail],
        ]);
        
        if ($emailSent) {
            error_log("Email de reçu envoyé à {$customerEmail} pour un paiement de {$amount} €");
        } else {
            error_log("Échec de l'envoi de l'email de reçu à {$customerEmail}");
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Paiement complété',
        'stock_updated' => $stockUpdated,
        'stock_result' => $stockResult,
        'email_sent' => $emailSent
    ]);
    exit;
}

// Route pour décrémenter le stock après un achat (ancienne route, conservée pour compatibilité)
if ($path === '/decrement-stock' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['article_id']) || empty($input['article_id'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'ID de l\'article requis'
        ]);
        exit;
    }
    
    $articleId = $input['article_id'];
    
    // Récupérer le projet depuis Back4app
    $projectResult = $back4app->getById('Project', $articleId);
    if (!($projectResult['success'] ?? false) || empty($projectResult['data'])) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Article non trouvé'
        ]);
        exit;
    }
    
    $project = $projectResult['data'];
    $currentQuantity = isset($project['quantite']) ? max(0, intval($project['quantite'])) : 0;
    $emailAlerte = $project['email_alerte'] ?? null;
    $projectTitle = $project['titre'] ?? 'Article';
    
    // Décrémenter la quantité de 1
    $newQuantity = max(0, $currentQuantity - 1);
    
    // Mettre à jour le stock dans Back4app
    $updateResult = $back4app->update('Project', $articleId, [
        'quantite' => $newQuantity,
        'updated_at' => date('c')
    ]);
    
    if (!($updateResult['success'] ?? false)) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Erreur lors de la mise à jour du stock'
        ]);
        exit;
    }
    
    error_log("Stock: Stock mis à jour pour '{$projectTitle}' - Ancien: {$currentQuantity}, Nouveau: {$newQuantity}");
    
    // Vérifier si le stock est en dessous de 5 et envoyer une alerte
    $alertSent = false;
    if ($newQuantity < 5 && !empty($emailAlerte) && filter_var($emailAlerte, FILTER_VALIDATE_EMAIL) && $emailHelper) {
        $subject = "⚠️ Alerte de stock faible - {$projectTitle}";
        $htmlBody = "
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .alert-box { background-color: #fff3cd; border: 2px solid #ffc107; border-radius: 8px; padding: 20px; margin: 20px 0; }
                    .alert-title { color: #856404; font-size: 20px; font-weight: bold; margin-bottom: 15px; }
                    .info { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                    .stock-value { font-size: 24px; font-weight: bold; color: #dc3545; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='alert-box'>
                        <div class='alert-title'>⚠️ Alerte de stock faible</div>
                        <p>Le stock de l'article suivant est maintenant en dessous de 5 unités :</p>
                        <div class='info'>
                            <strong>Article :</strong> {$projectTitle}<br>
                            <strong>Stock actuel :</strong> <span class='stock-value'>{$newQuantity}</span> unité(s)
                        </div>
                        <p><strong>Action requise :</strong> Veuillez procéder au réapprovisionnement de cet article.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        $textBody = "Alerte de stock faible\n\nArticle: {$projectTitle}\nStock actuel: {$newQuantity} unité(s)\n\nVeuillez procéder au réapprovisionnement.";
        
        $alertSent = $emailHelper->sendEmail($emailAlerte, $subject, $htmlBody, $textBody);
        
        if ($alertSent) {
            error_log("Stock: Email d'alerte envoyé à {$emailAlerte} pour '{$projectTitle}' (stock: {$newQuantity})");
        } else {
            error_log("Stock: Échec de l'envoi de l'email d'alerte à {$emailAlerte}");
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Stock mis à jour',
        'old_quantity' => $currentQuantity,
        'new_quantity' => $newQuantity,
        'alert_sent' => $alertSent
    ]);
    exit;
}

// Route pour supprimer un projet
if ($path === '/delete-project' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id']) || empty($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'ID du projet requis'
        ]);
        exit;
    }
    
    $result = $back4app->delete('Project', $input['id']);
    
    if ($result['success']) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Projet supprimé avec succès'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Erreur lors de la suppression: ' . ($result['data']['error'] ?? 'Erreur inconnue')
        ]);
    }
    exit;
}

// Route pour sauvegarder le code PIN
if ($path === '/save-pin' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['oldPin']) || !isset($input['newPin'])) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Ancien et nouveau code PIN requis'
        ]);
        exit;
    }
    
    // Récupérer le PIN existant depuis Back4app
    $result = $back4app->get('Pin', null, 1, '-createdAt');
    
    $storedPin = null;
    $objectId = null;
    
    if ($result['success'] && isset($result['data']['results']) && count($result['data']['results']) > 0) {
        $pinData = $result['data']['results'][0];
        $storedPin = $pinData['pin'] ?? null;
        $objectId = $pinData['objectId'] ?? null;
    }
    
    // Si aucun PIN n'existe, on crée le premier PIN
    if ($storedPin === null) {
        $data = [
            'pin' => $input['newPin'],
            'created_at' => date('c'),
            'updated_at' => date('c')
        ];
        
        $createResult = $back4app->create('Pin', $data);
        
        if ($createResult['success']) {
            pin_mark_validated($input['newPin']);
            echo json_encode([
                'status' => 'success',
                'message' => 'Code PIN créé avec succès'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Erreur lors de la sauvegarde: ' . ($createResult['data']['error'] ?? 'Erreur inconnue')
            ]);
        }
        exit;
    }
    
    // Vérifier que l'ancien PIN est correct
    if ($storedPin !== $input['oldPin']) {
        http_response_code(401);
        echo json_encode([
            'status' => 'error',
            'message' => 'Ancien code PIN incorrect'
        ]);
        exit;
    }
    
    // Vérifier que le nouveau PIN est différent de l'ancien
    if ($input['oldPin'] === $input['newPin']) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Le nouveau code PIN doit être différent de l\'ancien'
        ]);
        exit;
    }
    
    // Mettre à jour le PIN dans Back4app
    $updateData = [
        'pin' => $input['newPin'],
        'updated_at' => date('c')
    ];
    
    $updateResult = $back4app->update('Pin', $objectId, $updateData);
    
    if ($updateResult['success']) {
        pin_mark_validated($input['newPin']);
        echo json_encode([
            'status' => 'success',
            'message' => 'Code PIN modifié avec succès'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Erreur lors de la sauvegarde: ' . ($updateResult['data']['error'] ?? 'Erreur inconnue')
        ]);
    }
    exit;
}

// Route 404
http_response_code(404);
echo json_encode([
    'status' => 'error',
    'message' => 'Route non trouvée'
]);

