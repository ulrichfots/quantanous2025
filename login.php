<?php
define('AUTH_ALLOW_GOOGLE_PUBLIC', true);
require_once 'auth.php';

$configError = null;
$clientId = '';

try {
    $googleConfig = google_get_config();
    $clientId = $googleConfig['client_id'] ?? '';
} catch (RuntimeException $exception) {
    $configError = $exception->getMessage();
}
$redirect = $_GET['redirect'] ?? 'index.php';

if (google_is_authenticated()) {
    header('Location: ' . $redirect);
    exit;
}

?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2E7D32">
    <title>Connexion - quantanous 2025</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.12), rgba(255, 255, 255, 0.95));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 20px;
            padding: 36px 32px;
            box-shadow: 0 24px 60px rgba(46, 125, 50, 0.25);
            border: 1px solid rgba(46, 125, 50, 0.15);
            text-align: center;
        }
        .login-card h1 {
            font-size: 26px;
            font-weight: 800;
            color: #2E7D32;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }
        .login-card p {
            font-size: 15px;
            color: #4f4f4f;
            line-height: 1.7;
            margin-bottom: 28px;
        }
        .login-logo {
            font-family: 'Arial Black', Arial, sans-serif;
            font-size: 34px;
            color: #2E7D32;
            text-transform: uppercase;
            margin-bottom: 12px;
            display: inline-block;
        }
        .login-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(46, 125, 50, 0.3), transparent);
            margin: 24px 0;
        }
        #gSignInWrapper {
            display: flex;
            justify-content: center;
        }
        .login-error {
            background: rgba(244, 67, 54, 0.08);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: #b71c1c;
            border-radius: 10px;
            padding: 12px 16px;
            margin-top: 20px;
            display: none;
            font-size: 14px;
        }
        .login-links {
            margin-top: 32px;
            font-size: 13px;
            color: #616161;
        }
        .login-links a {
            color: #2E7D32;
            text-decoration: none;
        }
        .login-links a:hover {
            text-decoration: underline;
        }
    </style>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body>
    <div class="login-card" role="main">
        <div class="login-logo">quantanous 2025</div>
        <h1>Connexion requise</h1>
        <p>Merci de vous connecter avec votre compte Google autorisé pour accéder au journal.</p>
        <div id="gSignInWrapper"></div>
        <div class="login-divider"></div>
        <p>Votre session Google sera utilisée pour sécuriser l'accès aux pages administratives et aux paiements.</p>
        <div id="loginError" class="login-error"></div>
        <?php if ($configError): ?>
            <div class="login-error" style="display:block;"><?php echo htmlspecialchars($configError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <div class="login-links">
            <span>Besoin d'aide ? Contactez l'administrateur.</span>
        </div>
    </div>

    <script>
        const GOOGLE_CLIENT_ID = <?php echo json_encode($clientId, JSON_UNESCAPED_SLASHES); ?>;
        const REDIRECT_TARGET = <?php echo json_encode($redirect, JSON_UNESCAPED_SLASHES); ?>;
        const CONFIG_ERROR = <?php echo json_encode($configError, JSON_UNESCAPED_UNICODE); ?>;

        function displayError(message) {
            const box = document.getElementById('loginError');
            if (!box) return;
            box.textContent = message;
            box.style.display = 'block';
        }

        function handleCredentialResponse(response) {
            const credential = response.credential;
            if (!credential) {
                displayError("Impossible de récupérer le jeton d'authentification Google.");
                return;
            }

            fetch('api.php/google-login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    credential,
                    redirect: REDIRECT_TARGET
                })
            })
            .then(async (res) => {
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const message = data.message || "Authentification Google refusée.";
                    displayError(message);
                    google.accounts.id.disableAutoSelect();
                    return;
                }

                const redirect = data.redirect || REDIRECT_TARGET || 'index.php';
                window.location.href = redirect;
            })
            .catch(() => {
                displayError("Erreur de connexion au serveur. Merci de réessayer.");
            });
        }

        window.onload = function() {
            if (CONFIG_ERROR) {
                displayError(CONFIG_ERROR);
                return;
            }

            if (!GOOGLE_CLIENT_ID) {
                displayError("Client ID Google non configuré. Contactez l'administrateur.");
                return;
            }

            google.accounts.id.initialize({
                client_id: GOOGLE_CLIENT_ID,
                callback: handleCredentialResponse,
                auto_select: false,
                cancel_on_tap_outside: true,
                // Configuration FedCM (pour réduire les avertissements)
                use_fedcm_for_prompt: false, // Désactivé pour l'instant, activer quand FedCM sera obligatoire
            });

            const wrapper = document.getElementById('gSignInWrapper');
            if (wrapper) {
                google.accounts.id.renderButton(wrapper, {
                    theme: 'outline',
                    size: 'large',
                    type: 'standard',
                    shape: 'rectangular',
                    text: 'signin_with',
                    width: 280,
                });
            }

            google.accounts.id.prompt();
        };
    </script>
</body>
</html>
