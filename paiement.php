<?php
require_once 'auth.php';
$status = $_GET['status'] ?? '';
$message = '';
$messageClass = '';

if ($status === 'success') {
    $message = 'Paiement validé ! Un reçu vous a été envoyé par e-mail.';
    $messageClass = 'paiement-message success';
} elseif ($status === 'cancel') {
    $message = 'Le paiement a été annulé. Vous pouvez réessayer à tout moment.';
    $messageClass = 'paiement-message warning';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Paiement - quantanous">
    <meta name="theme-color" content="#2E7D32">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="quantanous">
    
    <title>Paiement - quantanous 2025</title>
    
    <!-- Manifest -->
    <link rel="manifest" href="manifest.php">
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="assets/icons/photobackground.JPG">
    <link rel="apple-touch-icon" href="assets/icons/photobackground.JPG">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Stripe.js -->
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <!-- Barre d'application supérieure -->
    <header class="app-header">
        <div class="header-content">
            <span class="header-title">quantanous 2025</span>
            <button class="menu-btn" id="menuBtn" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <!-- Menu contextuel -->
    <div class="context-menu" id="contextMenu">
        <div class="context-menu-item" data-page="pin">
            Modifier le code PIN
        </div>
        <div class="context-menu-item" data-page="presentation">
            Modifier la présentation
        </div>
        <div class="context-menu-item" data-page="achats">
            Modifier les achats
        </div>
        <div class="context-menu-item" data-page="explications">
            Modifier les explications
        </div>
    </div>

    <!-- Overlay pour fermer le menu -->
    <div class="menu-overlay" id="menuOverlay"></div>

    <!-- Contenu principal -->
    <main class="main-content">
        <div class="paiement-section">
            <h1 class="paiement-title">Paiement</h1>

            <?php if (!empty($message)): ?>
                <div class="<?= $messageClass ?>">
                    <?= htmlspecialchars($message) ?>
                    <?php if ($status === 'success'): ?>
                        <p style="margin-top: 12px; font-size: 14px; color: #666; font-style: italic;">
                            💡 Pensez à vérifier vos spams si vous ne recevez pas l'email.
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <p class="paiement-delivery-info" id="deliveryInfo">S'ajouteront des frais de livraison selon le tarif en vigeur</p>
            <p class="paiement-stripe-info">Le paiement est sécurisé via Stripe. Remplissez vos informations ci-dessous.</p>

            <form class="paiement-form" id="paiementForm">
                <!-- Informations personnelles -->
                <div class="form-row">
                    <div class="form-group half">
                        <label class="form-label">Nom de famille</label>
                        <input type="text" class="form-input" name="nom" required>
                    </div>
                    <div class="form-group half">
                        <label class="form-label">Prenom</label>
                        <input type="text" class="form-input" name="prenom" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full">
                        <label class="form-label">Adresse</label>
                        <input type="text" class="form-input" name="adresse" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group half">
                        <label class="form-label">Code postal</label>
                        <input type="text" class="form-input" name="code_postal" required pattern="[0-9]{5}">
                    </div>
                    <div class="form-group half">
                        <label class="form-label">Ville</label>
                        <input type="text" class="form-input" name="ville" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full">
                        <label class="form-label">E-mail</label>
                        <input type="email" class="form-input" name="email" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full">
                        <label class="form-label">Confirmer l'E-mail</label>
                        <input type="email" class="form-input" name="email_confirm" required>
                    </div>
                </div>

                <!-- Montant à payer -->
                <input type="hidden" name="montant" id="montantInput" value="10">
                <input type="hidden" name="article_id" id="articleIdInput" value="">

                <!-- Stripe Payment Element -->
                <div id="payment-element" style="margin: 24px 0;">
                    <!-- Stripe Elements sera injecté ici -->
                </div>

                <!-- Messages d'erreur -->
                <div id="payment-message" class="payment-message" style="display: none;"></div>

                <!-- Bouton de paiement -->
                <button type="submit" class="payer-btn" id="payerBtn">
                    PAYER <span id="montantDisplay">10 €</span>
                </button>
            </form>

            <!-- Message permanent en bas de la page -->
            <div class="paiement-email-info">
                <p>💡 Après le paiement, un reçu vous sera envoyé par email. Pensez à vérifier vos spams si vous ne recevez pas l'email.</p>
            </div>
        </div>
    </main>

    <!-- Barre de navigation inférieure -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
            </svg>
            <span class="nav-label">Presentation</span>
        </a>
        <a href="don.php" class="nav-item <?php echo (isset($_GET['from']) && $_GET['from'] === 'don') ? 'active' : ''; ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
            <span class="nav-label">Don</span>
        </a>
        <a href="achats.php" class="nav-item <?php echo (isset($_GET['from']) && $_GET['from'] === 'achats') || (!isset($_GET['from']) && isset($_GET['article_id']) && $_GET['article_id'] !== '') ? 'active' : ''; ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
            </svg>
            <span class="nav-label">Achats</span>
        </a>
        <a href="aide.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M11 18h2v-2h-2v2zm1-16C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 2-3 1.75-3 5h2c0-2.25 3-2.5 3-5 0-2.21-1.79-4-4-4z"/>
            </svg>
            <span class="nav-label">Pourquoi donner ?</span>
        </a>
    </nav>

    <!-- JavaScript -->
    <script src="assets/js/app.js"></script>
    <script src="assets/js/paiement.js"></script>
</body>
</html>

