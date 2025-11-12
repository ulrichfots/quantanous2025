<?php
require_once 'auth.php';
pin_require($_SERVER['REQUEST_URI'] ?? 'admin-achats.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Modifier les achats - Et Tout et Tout">
    <meta name="theme-color" content="#2E7D32">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Et Tout et Tout">
    
    <title>Modifier les achats - quantanous 2025</title>
    
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
        <div class="admin-achats-section">
            <!-- Section Nouveau Projet -->
            <div class="new-project-section">
                <h2 class="section-title">Nouveau Projet</h2>
                
                <form class="new-project-form" id="newProjectForm">
                    <div class="form-image-upload">
                        <label for="projectImages" class="image-upload-label">
                            <div class="image-placeholder">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                                </svg>
                                <span>Images</span>
                            </div>
                            <input type="file" id="projectImages" name="images[]" accept="image/*" multiple style="display: none;">
                        </label>
                        <p class="form-helper-text">Ajoutez une ou plusieurs images (6 maximum).</p>
                    </div>

                    <div class="images-preview-wrapper">
                        <div class="images-preview-grid" id="imagesPreviewList" aria-live="polite"></div>
                        <p class="images-preview-empty" id="imagesPreviewEmpty">Aucune image sélectionnée pour l'instant.</p>
                    </div>

                    <div class="form-group">
                        <input type="text" class="form-input" id="projectTitle" name="titre" placeholder="Titre du projet" required>
                    </div>

                    <div class="form-group">
                        <input type="text" class="form-input" id="projectDescription" name="description" placeholder="Description du projet" required>
                    </div>

                    <div class="form-row-checkbox">
                        <label class="checkbox-label">
                            <input type="checkbox" id="tvaIncluse" name="tva_incluse">
                            <span>TVA Incluse</span>
                        </label>
                        <div class="cost-input-wrapper">
                            <input type="number" class="form-input cost-input" id="projectCost" name="prix" placeholder="Coût €" min="0" step="0.01" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <input type="number" class="form-input" id="projectQuantity" name="quantite" placeholder="Quantité en stock" min="0" step="1" value="0">
                        <p class="form-helper-text">Quantité disponible en stock (pour les alertes de réapprovisionnement)</p>
                    </div>

                    <div class="form-group">
                        <input type="email" class="form-input" id="projectEmail" name="email_alerte" placeholder="Remplir un mail existant et accessible" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                        <p class="form-helper-text">Email pour recevoir les alertes de stock faible (en dessous de 5 unités)</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="add-project-btn">
                            AJOUTER UN PROJET
                        </button>
                        <button type="button" class="cancel-edit-btn hidden" id="cancelEditBtn">
                            ANNULER LA MODIFICATION
                        </button>
                    </div>
                </form>
            </div>

            <!-- Séparateur -->
            <div class="separator"></div>

            <!-- Liste des projets existants -->
            <div class="projects-list-section">
                <h2 class="section-title">Projets existants</h2>
                <div class="projects-list" id="projectsList">
                    <?php
                    require_once 'load-content.php';
                    $projects = loadProjectsFromBack4app();

                    // Afficher les projets
                    foreach ($projects as $project):
                    ?>
                    <?php
                        $projectPayload = [
                            'id' => $project['id'] ?? '',
                            'titre' => $project['titre'] ?? '',
                            'slug' => $project['titre_slug'] ?? '',
                            'description' => $project['description'] ?? '',
                            'prix' => $project['prix'] ?? 0,
                            'tva_incluse' => $project['tva_incluse'] ?? false,
                            'quantite' => $project['quantite'] ?? 0,
                            'email_alerte' => $project['email_alerte'] ?? null,
                        ];
                        $projectDataAttr = htmlspecialchars(json_encode($projectPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                        $previewImage = !empty($project['images'][0]) ? $project['images'][0] : ($project['image'] ?? 'assets/images/placeholder.jpg');
                    ?>
                    <div class="project-item" data-id="<?php echo htmlspecialchars($project['id'] ?? ''); ?>" data-project="<?php echo $projectDataAttr; ?>">
                        <div class="project-image-wrapper">
                            <img src="<?php echo htmlspecialchars($previewImage); ?>" 
                                 alt="<?php echo htmlspecialchars($project['titre'] ?? ''); ?>" 
                                 class="project-image"
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'120\'%3E%3Crect width=\'100\' height=\'120\' fill=\'%23E0E0E0\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\' font-family=\'Arial\' font-size=\'12\' fill=\'%23999\'%3EImage%3C/text%3E%3C/svg%3E'">
                        </div>
                        <div class="project-content">
                            <h3 class="project-title"><?php echo htmlspecialchars($project['titre'] ?? ''); ?></h3>
                            <p class="project-description"><?php echo htmlspecialchars($project['description'] ?? ''); ?></p>
                            <div class="project-price-info">
                                <span class="project-tva"><?php echo ($project['tva_incluse'] ?? false) ? 'TVA incluse' : ''; ?></span>
                                <span class="project-price"><?php echo number_format($project['prix'] ?? 0, 2, ',', ' '); ?> €</span>
                            </div>
                            <div class="project-stock-info">
                                <span class="project-stock">Stock: <?php echo isset($project['quantite']) ? intval($project['quantite']) : 0; ?> unité(s)</span>
                                <?php if (!empty($project['email_alerte'])): ?>
                                    <span class="project-email">Email alerte: <?php echo htmlspecialchars($project['email_alerte']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="project-actions">
                            <button class="edit-project-btn" data-id="<?php echo htmlspecialchars($project['id'] ?? ''); ?>">
                                MODIFIER
                            </button>
                            <button class="delete-project-btn" data-id="<?php echo htmlspecialchars($project['id'] ?? ''); ?>">
                                SUPPRIMER
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- JavaScript -->
    <script src="assets/js/app.js"></script>
    <script src="assets/js/admin-achats.js"></script>
</body>
</html>

