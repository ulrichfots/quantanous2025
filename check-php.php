<?php
/**
 * Script de vérification de la version PHP
 * Affiche la version PHP et vérifie les fonctionnalités requises
 */

echo "=== Vérification PHP ===\n\n";

// Version PHP actuelle
$phpVersion = phpversion();
echo "Version PHP actuelle : $phpVersion\n\n";

// Vérifier la version minimale requise
$minVersion = '7.4.0';
$currentVersion = version_compare($phpVersion, $minVersion, '>=');

if ($currentVersion) {
    echo "✅ Version PHP compatible (>= $minVersion)\n";
} else {
    echo "❌ Version PHP trop ancienne. Minimum requis : $minVersion\n";
}

echo "\n=== Fonctionnalités vérifiées ===\n\n";

// Vérifier les extensions nécessaires
$requiredExtensions = [
    'json' => 'JSON (nécessaire pour l\'API)',
    'mbstring' => 'Multibyte String (recommandé)',
    'gd' => 'GD (pour generate-icons.php)'
];

foreach ($requiredExtensions as $ext => $desc) {
    if (extension_loaded($ext)) {
        echo "✅ $ext : $desc\n";
    } else {
        echo "⚠️  $ext : $desc (non installée)\n";
    }
}

// Vérifier les fonctionnalités spécifiques
echo "\n=== Fonctionnalités du code ===\n\n";

// Opérateur null coalescing (PHP 7.0+)
$test = null ?? 'test';
if ($test === 'test') {
    echo "✅ Opérateur null coalescing (??) : Disponible\n";
} else {
    echo "❌ Opérateur null coalescing (??) : Non disponible\n";
}

// Syntaxe de tableau courte (PHP 5.4+)
$testArray = [];
if (is_array($testArray)) {
    echo "✅ Syntaxe de tableau courte ([]) : Disponible\n";
} else {
    echo "❌ Syntaxe de tableau courte ([]) : Non disponible\n";
}

// JSON
if (function_exists('json_encode') && function_exists('json_decode')) {
    echo "✅ Fonctions JSON : Disponibles\n";
} else {
    echo "❌ Fonctions JSON : Non disponibles\n";
}

// file_put_contents
if (function_exists('file_put_contents')) {
    echo "✅ file_put_contents : Disponible\n";
} else {
    echo "❌ file_put_contents : Non disponible\n";
}

// mkdir avec récursif
if (function_exists('mkdir')) {
    echo "✅ mkdir : Disponible\n";
} else {
    echo "❌ mkdir : Non disponible\n";
}

echo "\n=== Informations serveur ===\n\n";
echo "Serveur : " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "SAPI : " . php_sapi_name() . "\n";
echo "OS : " . PHP_OS . "\n";

echo "\n=== Recommandations ===\n\n";
echo "Version PHP recommandée : PHP 7.4 ou supérieur\n";
echo "Version PHP minimale : PHP 7.0\n";
echo "\nPour une meilleure performance et sécurité, utilisez PHP 8.0 ou supérieur.\n";

?>

