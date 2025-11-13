<?php
// Fichier pour servir le service-worker.js sans authentification Google
define('AUTH_ALLOW_GOOGLE_PUBLIC', true);

header('Content-Type: application/javascript; charset=utf-8');
header('Service-Worker-Allowed: /');
header('Cache-Control: public, max-age=3600');

readfile(__DIR__ . '/service-worker.js');
exit;

