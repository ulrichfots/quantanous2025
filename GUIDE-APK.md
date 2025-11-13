# Guide pour générer l'APK Android

## Méthode recommandée : PWABuilder (Microsoft)

PWABuilder est un outil gratuit de Microsoft qui permet de convertir votre PWA en APK Android facilement.

### Étapes :

1. **Accéder à PWABuilder**
   - Allez sur : https://www.pwabuilder.com/
   - Entrez l'URL de votre site : `https://quantanous2025.onrender.com`
   - Cliquez sur "Start"

2. **Vérifier les scores**
   - PWABuilder va analyser votre PWA
   - Vous devriez avoir des scores élevés pour Manifest, Service Worker, etc.
   - Si des améliorations sont suggérées, corrigez-les

3. **Générer l'APK**
   - Cliquez sur "Build My PWA"
   - Sélectionnez "Android"
   - Cliquez sur "Generate Package"
   - PWABuilder va générer un fichier ZIP contenant l'APK

4. **Télécharger et installer**
   - Téléchargez le fichier ZIP
   - Extrayez-le
   - Vous trouverez un fichier `.apk` à l'intérieur
   - Transférez ce fichier sur votre téléphone Android
   - Sur votre téléphone, allez dans Paramètres > Sécurité > Autoriser l'installation d'applications depuis des sources inconnues
   - Ouvrez le fichier APK et installez l'application

### Notes importantes :

- **Icônes** : Si PWABuilder demande des icônes, vous pouvez utiliser `assets/icons/photobackground.JPG` comme icône principale
- **URL** : Assurez-vous que votre site est accessible en ligne (ce qui est le cas avec Render)
- **HTTPS** : Votre site doit être en HTTPS (Render le fait automatiquement)

## Alternative : Android Studio (méthode avancée)

Si vous préférez une méthode plus technique avec plus de contrôle :

1. Installez Android Studio
2. Créez un nouveau projet "Trusted Web Activity"
3. Configurez l'URL de votre site
4. Générez l'APK depuis Android Studio

Cette méthode est plus complexe mais offre plus de personnalisation.

## Vérifications avant de générer l'APK

- ✅ Votre site est accessible en ligne (https://quantanous2025.onrender.com)
- ✅ Le manifest.json est correctement configuré
- ✅ Le service worker est actif
- ✅ L'application fonctionne correctement dans le navigateur mobile

