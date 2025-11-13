<?php
/**
 * Script pour générer les icônes PNG à partir de photobackground.JPG
 */

$sourceImage = __DIR__ . '/assets/icons/photobackground.JPG';
$sizes = [192, 512];

if (!file_exists($sourceImage)) {
    die("Erreur: Le fichier source $sourceImage n'existe pas.\n");
}

if (!function_exists('imagecreatefromjpeg')) {
    die("Erreur: L'extension GD n'est pas disponible.\n");
}

// Charger l'image source
$source = imagecreatefromjpeg($sourceImage);
if (!$source) {
    die("Erreur: Impossible de charger l'image source.\n");
}

$sourceWidth = imagesx($source);
$sourceHeight = imagesy($source);

echo "Image source: {$sourceWidth}x{$sourceHeight}\n";

foreach ($sizes as $size) {
    // Créer une image carrée de la taille souhaitée
    $icon = imagecreatetruecolor($size, $size);
    
    // Remplir avec un fond blanc (au cas où)
    $white = imagecolorallocate($icon, 255, 255, 255);
    imagefill($icon, 0, 0, $white);
    
    // Calculer les dimensions pour centrer et redimensionner l'image
    $ratio = min($size / $sourceWidth, $size / $sourceHeight);
    $newWidth = (int)($sourceWidth * $ratio);
    $newHeight = (int)($sourceHeight * $ratio);
    $x = (int)(($size - $newWidth) / 2);
    $y = (int)(($size - $newHeight) / 2);
    
    // Redimensionner et copier l'image
    imagecopyresampled(
        $icon, $source,
        $x, $y, 0, 0,
        $newWidth, $newHeight,
        $sourceWidth, $sourceHeight
    );
    
    // Sauvegarder en PNG
    $outputFile = __DIR__ . "/assets/icons/icon-{$size}x{$size}.png";
    if (imagepng($icon, $outputFile)) {
        echo "✓ Icône {$size}x{$size} créée: $outputFile\n";
    } else {
        echo "✗ Erreur lors de la création de l'icône {$size}x{$size}\n";
    }
    
    imagedestroy($icon);
}

imagedestroy($source);
echo "\nTerminé!\n";

