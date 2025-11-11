<?php
/**
 * Configuration Back4app
 * Définissez les variables d'environnement suivantes avant le déploiement :
 * BACK4APP_API_URL (optionnel, par défaut https://parseapi.back4app.com)
 * BACK4APP_APP_ID
 * BACK4APP_REST_KEY
 * BACK4APP_MASTER_KEY
 */

$apiUrl = getenv('BACK4APP_API_URL') ?: 'https://parseapi.back4app.com';
$applicationId = getenv('BACK4APP_APP_ID');
$restKey = getenv('BACK4APP_REST_KEY');
$masterKey = getenv('BACK4APP_MASTER_KEY');

foreach ([
    'BACK4APP_APP_ID' => $applicationId,
    'BACK4APP_REST_KEY' => $restKey,
    'BACK4APP_MASTER_KEY' => $masterKey,
] as $envName => $value) {
    if (!$value) {
        throw new RuntimeException("La variable d'environnement {$envName} est manquante.");
    }
}

return [
   'api_url'        => $apiUrl,
   'application_id' => $applicationId,
   'rest_api_key'   => $restKey,
   'master_key'     => $masterKey,
];

