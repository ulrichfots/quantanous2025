<?php require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Faire un don - Et Tout et Tout">
    <meta name="theme-color" content="#2E7D32">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Et Tout et Tout">
    
    <title>Faire un don - quantanous 2025</title>
    
    <!-- Manifest -->
    <link rel="manifest" href="manifest.php">
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="assets/icons/photobackground.JPG">
    <link rel="apple-touch-icon" href="assets/icons/photobackground.JPG">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
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
        <div class="context-menu-item" data-page="logout">
            Se déconnecter
        </div>
    </div>

    <!-- Overlay pour fermer le menu -->
    <div class="menu-overlay" id="menuOverlay"></div>

    <!-- Contenu principal -->
    <main class="main-content">
        <!-- Section Don -->
        <div class="don-section">
            <h1 class="don-title">Faire un don</h1>
            
            <!-- Options de fréquence -->
            <div class="frequency-options">
                <button class="frequency-btn" id="regulierBtn" data-frequency="regulier">
                    Régulier
                </button>
                <button class="frequency-btn" id="ponctuelBtn" data-frequency="ponctuel">
                    Ponctuel
                </button>
            </div>

            <!-- Options de périodicité pour les dons réguliers -->
            <div class="period-section hidden" id="periodSection" aria-hidden="true">
                <p class="period-title">Périodicité du don</p>
                <div class="period-options">
                    <button class="period-btn" data-interval="month" data-count="1" data-label="mensuel">
                        Mensuel
                    </button>
                    <button class="period-btn" data-interval="month" data-count="3" data-label="trimestriel">
                        Trimestriel
                    </button>
                    <button class="period-btn" data-interval="month" data-count="6" data-label="semestriel">
                        Semestriel
                    </button>
                    <button class="period-btn" data-interval="year" data-count="1" data-label="annuel">
                        Annuel
                    </button>
                </div>
                <p class="period-helper">
                    Sélectionnez la fréquence de renouvellement de votre don.
                </p>
            </div>

            <!-- Montants prédéfinis -->
            <div class="amount-options">
                <button class="amount-btn" data-amount="5">5 €</button>
                <button class="amount-btn" data-amount="10">10 €</button>
                <button class="amount-btn" data-amount="15">15 €</button>
                <button class="amount-btn" data-amount="20">20 €</button>
            </div>

            <!-- Saisie personnalisée -->
            <div class="custom-amount-section">
                <label for="customAmount" class="custom-amount-label">Ou saisir le montant :</label>
                <div class="custom-amount-input-wrapper">
                    <input type="number" 
                           id="customAmount" 
                           class="custom-amount-input" 
                           placeholder="0"
                           min="1"
                           step="1">
                    <span class="currency">€</span>
                </div>
            </div>

            <!-- Bouton Donner -->
            <button class="donate-btn" id="donateBtn">
                DONNER
            </button>
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
        <a href="don.php" class="nav-item active">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
            <span class="nav-label">Don</span>
        </a>
        <a href="achats.php" class="nav-item">
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
    <script src="assets/js/don.js"></script>
</body>
</html>

