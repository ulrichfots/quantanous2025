<?php require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Pourquoi donner ? - Et Tout et Tout">
    <meta name="theme-color" content="#2E7D32">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Et Tout et Tout">
    
    <title>Pourquoi donner ? - quantanous 2025</title>
    
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
        <div class="aide-section">
            <h1 class="aide-title">Pourquoi donner ?</h1>
            <h2 class="aide-subtitle">Un don = une action</h2>
            
            <div class="aide-content" id="aideContent">
                <?php
                require_once 'load-content.php';
                $defaultContent = 'nous permettez d\'affecter une part plus importante de vos dons à nos actions.

Une totale liberté

Vous êtes libre à tout moment de mettre fin ou de modifier ce prélèvement sur simple demande auprès du service donateurs de l association et tout :
- soit par téléphone au 09 70 82 05 05 -
- soit par mail à donateurs@croix-rouge.fr -
- soit par courrier au 98, rue Didot - 75694 PARIS Cedex 14

Soutenez-nous tous les mois

Au quotidien, nos bénévoles sauvent la vie et viennent en aide aux personnes handicapé

Si L\'association et tout et tout peut mener à bien ses actions, c\'est grâce à vos dons, vos dons mensuels qui nous assurent des ressources stables. Grâce à ce soutien régulier, nous pouvons être plus réactif, plus efficace, par de meilleures actions sur le terrain.';
                $contenu = loadContentFromBack4app('explications', $defaultContent);
                
                // Convertir le contenu HTML en paragraphes si nécessaire
                // Si le contenu contient déjà du HTML, on l'affiche tel quel
                if (strip_tags($contenu) === $contenu) {
                    // Le contenu est du texte brut, on le convertit en paragraphes
                    $paragraphes = preg_split('/\n\s*\n/', trim($contenu));
                    foreach ($paragraphes as $paragraphe) {
                        $paragraphe = trim($paragraphe);
                        if (!empty($paragraphe)) {
                            // Vérifier si c'est une liste (commence par -)
                            if (preg_match('/^-\s/', $paragraphe)) {
                                echo '<p>' . htmlspecialchars($paragraphe) . '</p>';
                            } else {
                                echo '<p>' . nl2br(htmlspecialchars($paragraphe)) . '</p>';
                            }
                        }
                    }
                } else {
                    // Le contenu contient déjà du HTML
                    echo $contenu;
                }
                ?>
            </div>
            
            <!-- Pagination automatique -->
            <div class="aide-pagination" id="aidePagination">
                <button class="pagination-btn pagination-prev" id="paginationPrev" style="display: none;">
                    ‹ Précédent
                </button>
                <span class="pagination-info" id="paginationInfo"></span>
                <button class="pagination-btn pagination-next" id="paginationNext" style="display: none;">
                    Suivant ›
                </button>
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
        <a href="achats.php" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
            </svg>
            <span class="nav-label">Achats</span>
        </a>
        <a href="aide.php" class="nav-item active">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M11 18h2v-2h-2v2zm1-16C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 2-3 1.75-3 5h2c0-2.25 3-2.5 3-5 0-2.21-1.79-4-4-4z"/>
            </svg>
            <span class="nav-label">Pourquoi donner ?</span>
        </a>
    </nav>

    <!-- JavaScript -->
    <script src="assets/js/app.js"></script>
    <script src="assets/js/aide.js"></script>
</body>
</html>

