<?php
require_once 'auth.php';
pin_require($_SERVER['REQUEST_URI'] ?? 'admin-explications.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Modifier les explications - Et Tout et Tout">
    <meta name="theme-color" content="#2E7D32">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Et Tout et Tout">
    
    <title>Modifier les explications - quantanous 2025</title>
    
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
    <main class="main-content">
        <div class="editor-section">
            <div class="editor-container" id="editor" contenteditable="true">
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
                
                // Si le contenu contient du HTML, l'afficher tel quel, sinon l'échapper
                if (strip_tags($contenu) === $contenu) {
                    echo htmlspecialchars($contenu);
                } else {
                    echo $contenu;
                }
                ?>
            </div>
        </div>
    </main>

    <!-- Barre d'outils de formatage -->
    <div class="format-toolbar">
        <button class="format-btn" data-command="bold" aria-label="Gras" title="Gras">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M15.6 10.79c.97-.67 1.65-1.77 1.65-2.79 0-2.26-1.75-4-4-4H7v14h7.04c2.09 0 3.71-1.7 3.71-3.79 0-1.52-.86-2.82-2.15-3.42zM10 6.5h3c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5h-3v-3zm3.5 9H10v-3h3.5c.83 0 1.5.67 1.5 1.5s-.67 1.5-1.5 1.5z"/>
            </svg>
        </button>
        <button class="format-btn" data-command="italic" aria-label="Italique" title="Italique">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4z"/>
            </svg>
        </button>
        <button class="format-btn" data-command="underline" aria-label="Souligné" title="Souligné">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 17c3.31 0 6-2.69 6-6V3h-2.5v8c0 1.93-1.57 3.5-3.5 3.5S8.5 12.93 8.5 11V3H6v8c0 3.31 2.69 6 6 6zm-7 2v2h14v-2H5z"/>
            </svg>
        </button>
        <button class="format-btn" data-command="strikeThrough" aria-label="Barré" title="Barré">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M7.24 8.75c-.26-.48-.39-1.03-.39-1.64 0-1.64 1.28-2.96 2.85-2.96.74 0 1.41.27 1.92.78l.71-.71C11.43 3.93 10.65 3.5 9.7 3.5c-2.34 0-4.2 1.88-4.2 4.11 0 1.02.38 1.94 1 2.64.31.35.67.64 1.07.85l-.71.71zm12.5 4.5c.26.48.39 1.03.39 1.64 0 1.64-1.28 2.96-2.85 2.96-.74 0-1.41-.27-1.92-.78l-.71.71c.91.91 1.69 1.34 2.64 1.34 2.34 0 4.2-1.88 4.2-4.11 0-1.02-.38-1.94-1-2.64-.31-.35-.67-.64-1.07-.85l.71-.71zM5.5 12h13v-2h-13v2z"/>
            </svg>
        </button>
        <button class="format-btn" data-command="insertUnorderedList" aria-label="Liste" title="Liste">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M4 10.5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zm0-6c-.83 0-1.5.67-1.5 1.5S3.17 7.5 4 7.5 5.5 6.83 5.5 6 4.83 4.5 4 4.5zm0 12c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5 1.5-.67 1.5-1.5-.67-1.5-1.5-1.5zM7 19h14v-2H7v2zm0-6h14v-2H7v2zm0-8v2h14V5H7z"/>
            </svg>
        </button>
        <button class="format-btn" data-command="formatBlock" data-value="blockquote" aria-label="Citation" title="Citation">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/>
            </svg>
        </button>
        <button class="format-btn" data-command="createLink" aria-label="Lien" title="Lien">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/>
            </svg>
        </button>
        <button class="format-btn" data-command="removeFormat" aria-label="Effacer formatage" title="Effacer formatage">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l-2.13.61C18.5 12.5 15.76 10 12.5 10z"/>
            </svg>
        </button>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/app.js"></script>
    <script src="assets/js/editor.js"></script>
</body>
</html>

