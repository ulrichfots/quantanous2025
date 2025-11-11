# Guide de création des icônes

## Méthode rapide : Générer avec PHP

1. **Créer ou trouver une image source**
   - Format : PNG, JPEG ou GIF
   - Taille recommandée : 512x512 pixels minimum
   - Placez-la dans le répertoire racine du projet

2. **Exécuter le script de génération**
   ```bash
   php generate-icons.php votre-image.png
   ```

3. **Vérifier les icônes générées**
   - Les icônes seront créées dans `assets/icons/`
   - Toutes les tailles nécessaires seront générées automatiquement

## Méthode alternative : Outils en ligne

### PWA Builder Image Generator
1. Allez sur https://www.pwabuilder.com/imageGenerator
2. Uploadez votre image source (512x512 recommandé)
3. Téléchargez le package d'icônes généré
4. Extrayez les icônes dans `assets/icons/`

### RealFaviconGenerator
1. Allez sur https://realfavicongenerator.net/
2. Uploadez votre image
3. Configurez les options
4. Téléchargez et extrayez les icônes

## Création manuelle

Si vous préférez créer les icônes manuellement, vous devez générer les tailles suivantes :

- `icon-72x72.png`
- `icon-96x96.png`
- `icon-128x128.png`
- `icon-144x144.png`
- `icon-152x152.png`
- `icon-192x192.png` ⭐ **Minimum requis**
- `icon-384x384.png`
- `icon-512x512.png` ⭐ **Minimum requis**

## Conseils de design

- **Fond transparent** : Utilisez PNG avec transparence
- **Carré** : Les icônes doivent être carrées (ratio 1:1)
- **Contraste** : Assurez-vous que l'icône est visible sur fond clair et foncé
- **Simplicité** : Évitez les détails trop fins, ils seront perdus dans les petites tailles
- **Couleurs vives** : Utilisez des couleurs qui se démarquent

## Test rapide

Pour tester rapidement sans icône personnalisée, vous pouvez créer une icône simple avec ce code PHP :

```php
<?php
// test-icon.php
$size = 512;
$img = imagecreatetruecolor($size, $size);
$bg = imagecolorallocate($img, 33, 150, 243); // Bleu
$text = imagecolorallocate($img, 255, 255, 255); // Blanc
imagefill($img, 0, 0, $bg);
imagestring($img, 5, $size/2-50, $size/2-10, 'PWA', $text);
imagepng($img, 'assets/icons/icon-512x512.png');
imagedestroy($img);
echo "Icône de test créée !\n";
?>
```

Exécutez : `php test-icon.php` puis `php generate-icons.php assets/icons/icon-512x512.png`

