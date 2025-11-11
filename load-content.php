<?php
/**
 * Helper pour charger le contenu depuis Back4app
 */

require_once 'back4app-helper.php';

function loadContentFromBack4app($type, $defaultContent = '') {
    try {
        $back4app = new Back4AppHelper();
        $result = $back4app->get('Content', ['type' => $type], 1);
        
        if ($result['success'] && isset($result['data']['results']) && count($result['data']['results']) > 0) {
            return $result['data']['results'][0]['content'] ?? $defaultContent;
        }
    } catch (Exception $e) {
        // En cas d'erreur, retourner le contenu par défaut
        error_log('Erreur lors du chargement du contenu depuis Back4app: ' . $e->getMessage());
    }
    
    return $defaultContent;
}

function loadProjectsFromBack4app() {
    try {
        $back4app = new Back4AppHelper();
        $result = $back4app->get('Project', null, 100, '-createdAt');
        
        if ($result['success'] && isset($result['data']['results'])) {
            return array_map(function($item) {
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
                    'titre_slug' => $item['titre_slug'] ?? '',
                    'description' => $item['description'] ?? '',
                    'prix' => $item['prix'] ?? 0,
                    'tva_incluse' => $item['tva_incluse'] ?? false,
                    'image' => $primaryImage,
                    'images' => $images,
                    'timestamp' => $item['created_at'] ?? date('Y-m-d H:i:s')
                ];
            }, $result['data']['results']);
        }
    } catch (Exception $e) {
        error_log('Erreur lors du chargement des projets depuis Back4app: ' . $e->getMessage());
    }
    
    return [];
}

