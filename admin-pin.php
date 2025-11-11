<?php
require_once 'auth.php';
pin_require($_SERVER['REQUEST_URI'] ?? 'admin-pin.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Modifier le code PIN - Et Tout et Tout">
    <meta name="theme-color" content="#2E7D32">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Et Tout et Tout">
    
    <title>Modifier le code PIN - quantanous 2025</title>
    
    <!-- Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/icons/icon-192x192.png">
    <link rel="apple-touch-icon" href="assets/icons/icon-192x192.png">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Barre d'application supérieure -->
    <header class="app-header">
        <div class="header-content">
            <span class="header-title">quantanous 2025</span>
            <button class="save-btn" id="saveBtn" aria-label="Sauvegarder">
                <svg class="save-icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                </svg>
            </button>
        </div>
    </header>

    <!-- Contenu principal -->
    <main class="main-content pin-admin-main">
        <section class="pin-section">
            <div class="pin-card">
                <header class="pin-admin-header">
                    <div class="pin-admin-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 1C8.14 1 5 4.14 5 8v3H4c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-8c0-1.1-.9-2-2-2h-1V8c0-3.86-3.14-7-7-7zm0 2c2.76 0 5 2.24 5 5v3H7V8c0-2.76 2.24-5 5-5zm-6 8h12c.55 0 1 .45 1 1v7c0 .55-.45 1-1 1H6c-.55 0-1-.45-1-1v-7c0-.55.45-1 1-1z"/>
                        </svg>
                    </div>
                    <div class="pin-admin-texts">
                        <h1 class="pin-admin-title">Modifier le code PIN</h1>
                        <p class="pin-admin-subtitle">Sécurisez l’accès aux pages d’administration en choisissant un code à 6 chiffres facile à retenir pour l’équipe.</p>
                    </div>
                </header>

                <div class="pin-current-info" role="note">
                    <span class="pin-pill-label">PIN actuel</span>
                    <span class="pin-pill-value">271244 (par défaut)</span>
                    <span class="pin-pill-hint">Pensez à le changer régulièrement.</span>
                </div>

                <div class="pin-inputs-grid">
                    <!-- Ancien PIN -->
                    <div class="pin-group">
                        <h2 class="pin-label">Ancien code PIN</h2>
                        <p class="pin-helper">Entrez le code utilisé actuellement pour valider le changement.</p>
                        <div class="pin-display" id="oldPinDisplay">
                            <div class="pin-dot"></div>
                            <div class="pin-dot"></div>
                            <div class="pin-dot"></div>
                            <div class="pin-dot"></div>
                            <div class="pin-dot"></div>
                            <div class="pin-dot"></div>
                        </div>
                        <input type="hidden" id="oldPin" maxlength="6">
                    </div>

                    <!-- Nouveau PIN -->
                    <div class="pin-group">
                        <h2 class="pin-label">Nouveau code PIN</h2>
                        <p class="pin-helper">Choisissez un code simple à communiquer mais difficile à deviner.</p>
                        <div class="pin-display" id="newPinDisplay">
                            <div class="pin-dot"></div>
                            <div class="pin-dot"></div>
                            <div class="pin-dot"></div>
                            <div class="pin-dot"></div>
                            <div class="pin-dot"></div>
                            <div class="pin-dot"></div>
                        </div>
                        <input type="hidden" id="newPin" maxlength="6">
                    </div>

                    <!-- Confirmation PIN -->
                    <div class="pin-group">
                        <h2 class="pin-label">Confirmer le nouveau code PIN</h2>
                        <p class="pin-helper">Retapez le code pour valider qu’il n’y a aucune erreur.</p>
                        <div class="pin-display" id="confirmPinDisplay">
                            <div class="pin-dot"></div>
                            <div class="pin-dot"></div>
                            <div class="pin-dot"></div>
                            <div class="pin-dot"></div>
                            <div class="pin-dot"></div>
                            <div class="pin-dot"></div>
                        </div>
                        <input type="hidden" id="confirmPin" maxlength="6">
                    </div>
                </div>

                <div class="pin-error" id="pinError" role="alert"></div>

                <div class="pin-keyboard">
                    <div class="keyboard-row">
                        <button class="key-btn" data-key="1">1</button>
                        <button class="key-btn" data-key="2">2</button>
                        <button class="key-btn" data-key="3">3</button>
                    </div>
                    <div class="keyboard-row">
                        <button class="key-btn" data-key="4">4</button>
                        <button class="key-btn" data-key="5">5</button>
                        <button class="key-btn" data-key="6">6</button>
                    </div>
                    <div class="keyboard-row">
                        <button class="key-btn" data-key="7">7</button>
                        <button class="key-btn" data-key="8">8</button>
                        <button class="key-btn" data-key="9">9</button>
                    </div>
                    <div class="keyboard-row">
                        <button class="key-btn" data-key="0">0</button>
                        <button class="key-btn key-delete" id="deleteBtn" aria-label="Effacer">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M22 3H7c-.69 0-1.23.35-1.59.88L0 12l5.41 8.11c.36.53.9.89 1.59.89h15c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H7.07L2.4 12l4.67-7H22v14zm-11.59-2L14 13.41 17.59 17 19 15.59 15.41 12 19 8.41 17.59 7 14 10.59 10.41 7 9 8.41 12.59 12 9 15.59z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="pin-guidelines">
                    <h3>Conseils pratiques</h3>
                    <ul>
                        <li>Évitez les suites évidentes (000000, 123456).</li>
                        <li>Communiquez le code uniquement aux personnes habilitées.</li>
                        <li>Changez-le si vous suspectez une utilisation non autorisée.</li>
                    </ul>
                </div>
            </div>
        </section>
    </main>

    <!-- JavaScript -->
    <script src="assets/js/app.js"></script>
    <script src="assets/js/pin.js"></script>
</body>
</html>

