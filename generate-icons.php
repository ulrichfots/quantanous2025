<?php
/**
 * Script pour générer les icônes PWA à partir d'une image source
 * 
 * Usage: php generate-icons.php source.png
 * 
 * Requiert: GD Library (extension PHP)
 */

if (!extension_loaded('gd')) {
    die("L'extension GD n'est pas installée. Installez-la pour utiliser ce script.\n");
}

if ($argc < 2) {
    die("Usage: php generate-icons.php chemin/vers/image-source.png\n");
}

$sourceImage = $argv[1];

if (!file_exists($sourceImage)) {
    die("Erreur: Le fichier source '$sourceImage' n'existe pas.\n");
}

// Tailles d'icônes requises
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

// Créer le répertoire des icônes s'il n'existe pas
$iconsDir = __DIR__ . '/assets/icons';
if (!is_dir($iconsDir)) {
    mkdir($iconsDir, 0755, true);
}

// Charger l'image source
$imageInfo = getimagesize($sourceImage);
$mimeType = $imageInfo['mime'];

switch ($mimeType) {
    case 'image/png':
        $source = imagecreatefrompng($sourceImage);
        break;
    case 'image/jpeg':
    case 'image/jpg':
        $source = imagecreatefromjpeg($sourceImage);
        break;
    case 'image/gif':
        $source = imagecreatefromgif($sourceImage);
        break;
    default:
        die("Erreur: Format d'image non supporté. Utilisez PNG, JPEG ou GIF.\n");
}

if (!$source) {
    die("Erreur: Impossible de charger l'image source.\n");
}

echo "Génération des icônes...\n";

foreach ($sizes as $size) {
    // Créer une nouvelle image redimensionnée
    $icon = imagecreatetruecolor($size, $size);
    
    // Activer la transparence pour PNG
    imagealphablending($icon, false);
    imagesavealpha($icon, true);
    $transparent = imagecolorallocatealpha($icon, 0, 0, 0, 127);
    imagefill($icon, 0, 0, $transparent);
    
    // Redimensionner l'image source
    imagecopyresampled(
        $icon,
        $source,
        0, 0, 0, 0,
        $size, $size,
        $imageInfo[0], $imageInfo[1]
    );
    
    // Sauvegarder l'icône
    $outputPath = $iconsDir . "/icon-{$size}x{$size}.png";
    imagepng($icon, $outputPath);
    imagedestroy($icon);
    
    echo "✓ Généré: icon-{$size}x{$size}.png\n";
}

imagedestroy($source);

echo "\n✅ Toutes les icônes ont été générées avec succès dans: $iconsDir\n";
echo "📝 Vous pouvez maintenant utiliser votre PWA.\n";

