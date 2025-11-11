<?php require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Achats - Et Tout et Tout">
    <meta name="theme-color" content="#2E7D32">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Et Tout et Tout">
    
    <title>Achats - quantanous 2025</title>
    
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
        <div class="achats-section">
            <!-- Liste des articles -->
            <div class="achats-list">
                <!-- Les articles sont chargés depuis Back4app -->
                <?php
                require_once 'load-content.php';
                $articles = loadProjectsFromBack4app();

                // Afficher tous les articles
                foreach ($articles as $article):
                ?>
                <div class="achat-item">
                    <div class="achat-image-wrapper">
                        <img src="<?php echo htmlspecialchars($article['image'] ?: 'assets/images/placeholder.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($article['titre']); ?>" 
                             class="achat-image"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'120\'%3E%3Crect width=\'100\' height=\'120\' fill=\'%23E0E0E0\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\' font-family=\'Arial\' font-size=\'12\' fill=\'%23999\'%3EImage%3C/text%3E%3C/svg%3E'">
                    </div>
                    <div class="achat-content">
                        <h3 class="achat-title"><?php echo htmlspecialchars($article['titre']); ?></h3>
                        <p class="achat-description"><?php echo htmlspecialchars($article['description']); ?></p>
                        <div class="achat-price-info">
                            <span class="achat-tva"><?php echo ($article['tva_incluse'] ?? false) ? 'TVA incluse' : ''; ?></span>
                            <span class="achat-price"><?php echo number_format($article['prix'] ?? 0, 2, ',', ' '); ?> €</span>
                        </div>
                    </div>
                    <button class="achat-donner-btn" data-article-id="<?php echo $article['id']; ?>" data-prix="<?php echo $article['prix']; ?>">
                        ACHAT
                    </button>
                </div>
                <?php endforeach; ?>
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
        <a href="don.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
            <span class="nav-label">Don</span>
        </a>
        <a href="achats.php" class="nav-item active">
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
    <script src="assets/js/achats.js"></script>
</body>
</html>

