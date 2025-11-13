<?php
// Fichier pour servir le manifest.json sans authentification Google
define('AUTH_ALLOW_GOOGLE_PUBLIC', true);

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

readfile(__DIR__ . '/manifest.json');
exit;

