<?php
/**
 * Fichier de configuration de l'application PWA
 * Personnalisez ces valeurs selon vos besoins
 */

return [
    // Informations de l'application
    'app_name' => 'Mon Application PWA',
    'app_short_name' => 'MonApp',
    'app_description' => 'Application Progressive Web App développée en PHP',
    
    // Couleurs du thème
    'theme_color' => '#2196F3',
    'background_color' => '#ffffff',
    
    // Configuration de l'affichage
    'display_mode' => 'standalone', // standalone, fullscreen, minimal-ui, browser
    'orientation' => 'portrait-primary', // portrait-primary, landscape, etc.
    
    // URL de démarrage
    'start_url' => '/',
    
    // Configuration du cache
    'cache_version' => 'v1',
    'cache_name' => 'mon-app-pwa-v1',
    
    // Configuration API
    'api_base_url' => '/api.php',
    
    // Mode développement
    'debug' => true,
    
    // Informations serveur
    'server_timezone' => 'Europe/Paris',
];

