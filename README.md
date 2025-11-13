# Application PWA en PHP

Application Progressive Web App développée en PHP, pouvant être convertie en APK Android.

## 🚀 Installation

### Prérequis
- Serveur web avec PHP 7.4+ (Apache/Nginx)
- HTTPS (requis pour les PWA en production)
- Navigateur moderne supportant les PWA

### Démarrage rapide

1. **Placer les fichiers sur votre serveur**
   ```bash
   # Copier tous les fichiers dans le répertoire web de votre serveur
   ```

2. **Générer les icônes**
   - Créez une icône principale de 512x512 pixels
   - Placez-la dans `assets/icons/icon-512x512.png`
   - Utilisez un outil en ligne comme [PWA Asset Generator](https://www.pwabuilder.com/imageGenerator) pour générer toutes les tailles
   - Ou utilisez le script `generate-icons.php` (voir ci-dessous)

3. **Configurer le serveur**
   - Assurez-vous que le fichier `.htaccess` est actif (Apache)
   - Pour Nginx, configurez les headers appropriés

4. **Accéder à l'application**
   - Ouvrez `http://localhost/index.php` dans votre navigateur
   - Ou déployez sur un serveur avec HTTPS

## 📱 Générer un APK à partir de la PWA

### Méthode 1 : PWA Builder (Recommandé - Gratuit et Simple)

**Votre site est déjà en ligne :** `https://quantanous2025.onrender.com`

1. **Aller sur [PWA Builder](https://www.pwabuilder.com/)**

2. **Entrer l'URL de votre PWA**
   - Collez : `https://quantanous2025.onrender.com`
   - Cliquez sur "Start"

3. **Analyser votre PWA**
   - PWA Builder va vérifier votre manifest.json et service worker
   - Vous devriez avoir des scores élevés (90+)
   - Si des améliorations sont suggérées, notez-les

4. **Générer l'APK**
   - Cliquez sur "Build My PWA"
   - Sélectionnez "Android"
   - Cliquez sur "Generate Package"
   - Téléchargez le fichier ZIP généré

5. **Installer l'APK sur votre téléphone**
   - Extrayez le fichier ZIP
   - Transférez le fichier `.apk` sur votre téléphone Android
   - Sur votre téléphone : **Paramètres > Sécurité > Autoriser l'installation d'applications depuis des sources inconnues**
   - Ouvrez le fichier APK et installez l'application

**Note :** Si PWABuilder demande des icônes, vous pouvez utiliser `assets/icons/photobackground.JPG` comme icône principale.

### Méthode 2 : Trusted Web Activity (TWA) - Manuel

1. **Installer Android Studio**

2. **Créer un projet TWA**
   ```bash
   # Utiliser le template Bubblewrap
   npm install -g @bubblewrap/cli
   bubblewrap init --manifest=https://votre-domaine.com/manifest.json
   bubblewrap build
   ```

3. **Compiler l'APK**
   - Ouvrir le projet dans Android Studio
   - Build → Generate Signed Bundle / APK

### Méthode 3 : Capacitor (Alternative)

1. **Installer Capacitor**
   ```bash
   npm install -g @capacitor/cli
   ```

2. **Initialiser le projet**
   ```bash
   capacitor init
   capacitor add android
   ```

3. **Configurer et compiler**
   - Modifier `capacitor.config.json` pour pointer vers votre serveur PHP
   - Compiler avec Android Studio

## 🎨 Générer les icônes

### Option 1 : Script PHP (à créer)

Créez un fichier `generate-icons.php` pour générer automatiquement les icônes à partir d'une image source.

### Option 2 : Outils en ligne

- [PWA Asset Generator](https://www.pwabuilder.com/imageGenerator)
- [RealFaviconGenerator](https://realfavicongenerator.net/)
- [App Icon Generator](https://appicon.co/)

### Tailles d'icônes requises

- 72x72
- 96x96
- 128x128
- 144x144
- 152x152
- 192x192
- 384x384
- 512x512

## 📂 Structure du projet

```
quantanous/
├── index.php              # Page principale
├── api.php                # API PHP
├── manifest.json          # Manifest PWA
├── service-worker.js      # Service Worker pour offline
├── .htaccess             # Configuration Apache
├── assets/
│   ├── css/
│   │   └── style.css     # Styles
│   ├── js/
│   │   ├── app.js        # Logique principale
│   │   └── install.js    # Installation PWA
│   └── icons/            # Icônes (à générer)
└── README.md             # Ce fichier
```

## 🔧 Configuration

### Modifier le manifest.json

Éditez `manifest.json` pour personnaliser :
- Nom de l'application
- Couleurs du thème
- Icônes
- URL de démarrage

### Modifier le service worker

Le fichier `service-worker.js` gère :
- La mise en cache
- Le mode hors ligne
- Les mises à jour

### Variables d'environnement (sécurité)

Toutes les clés sensibles doivent être stockées dans un fichier `.env` (non versionné) ou dans les variables d'environnement de votre hébergeur. Exemple de contenu :

```
# Back4App
BACK4APP_API_URL=https://parseapi.back4app.com
BACK4APP_APP_ID=VOTRE_APPLICATION_ID
BACK4APP_REST_KEY=VOTRE_REST_API_KEY
BACK4APP_MASTER_KEY=VOTRE_MASTER_KEY

# Stripe
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_SHIPPING_FEE=5.00
STRIPE_CURRENCY=eur
STRIPE_COMPANY_NAME="Et Tout et Tout"
STRIPE_EMAIL_FROM=no-reply@example.com

# Google OAuth
GOOGLE_CLIENT_ID=votreadresse.apps.googleusercontent.com

# URL de base (optionnel)
BASE_URL=https://mon-domaine.com
```

> **Important :** le fichier `.env` est déjà ajouté au `.gitignore`. Ne committez jamais vos clés en clair.

### API PHP

Le fichier `api.php` contient une API REST simple :
- `GET /api.php` - Informations générales
- `GET /api.php/test` - Test API
- `POST /api.php/data` - Envoi de données

## ✅ Checklist avant génération APK

- [ ] Application accessible via HTTPS
- [ ] Manifest.json valide et complet
- [ ] Service Worker fonctionnel
- [ ] Toutes les icônes générées et présentes
- [ ] Application testée sur mobile
- [ ] Mode offline fonctionnel
- [ ] Pas d'erreurs dans la console

## 🌐 Déploiement

### Option 1 : Serveur local (Développement)
```bash
php -S localhost:8000
```

### Option 2 : Render (Recommandé pour Production)

Le projet contient un fichier `render.yaml` pour un déploiement automatique sur Render.

#### Étapes de déploiement :

1. **Créer un compte sur [Render](https://render.com)**

2. **Connecter votre repository GitHub**
   - Dans le dashboard Render, cliquez sur "New" → "Web Service"
   - Connectez votre repository GitHub `ulrichfots/quantanous2025`
   - Render détectera automatiquement le fichier `render.yaml`

3. **Configurer les variables d'environnement**
   
   Dans le dashboard Render, allez dans "Environment" et ajoutez toutes les variables suivantes :
   
   **Back4App :**
   - `BACK4APP_APP_ID` : Votre Application ID Back4App
   - `BACK4APP_REST_KEY` : Votre REST API Key
   - `BACK4APP_MASTER_KEY` : Votre Master Key
   
   **Stripe :**
   - `STRIPE_PUBLISHABLE_KEY` : Votre clé publique Stripe
   - `STRIPE_SECRET_KEY` : Votre clé secrète Stripe
   - `STRIPE_WEBHOOK_SECRET` : Le secret de votre webhook Stripe
   - `STRIPE_EMAIL_FROM` : Adresse email pour les reçus (ex: noreply@votredomaine.com)
   - `STRIPE_SHIPPING_FEE` : `5.00` (déjà configuré dans render.yaml)
   - `STRIPE_CURRENCY` : `eur` (déjà configuré dans render.yaml)
   
   **Google OAuth :**
   - `GOOGLE_CLIENT_ID` : Votre Client ID Google OAuth
   - `GOOGLE_ALLOWED_DOMAINS` : (optionnel) Domaines autorisés séparés par des virgules
   - `GOOGLE_ALLOWED_EMAILS` : (optionnel) Emails autorisés séparés par des virgules
   
   **Base URL :**
   - `BASE_URL` : Sera automatiquement défini par Render (pas besoin de le configurer)

4. **Configurer le webhook Stripe**
   - Une fois déployé, copiez l'URL de votre service Render (ex: `https://quantanous-pwa.onrender.com`)
   - Dans le dashboard Stripe, allez dans "Developers" → "Webhooks"
   - Ajoutez un endpoint : `https://votre-service.onrender.com/api.php/stripe-webhook`
   - Sélectionnez les événements : `checkout.session.completed` et `invoice.payment_succeeded`
   - Copiez le "Signing secret" et ajoutez-le dans Render comme `STRIPE_WEBHOOK_SECRET`

5. **Déployer**
   - Render déploiera automatiquement à chaque push sur la branche `main`
   - La première fois, cliquez sur "Manual Deploy" → "Deploy latest commit"

6. **Vérifier le déploiement**
   - Une fois déployé, votre application sera accessible sur `https://votre-service.onrender.com`
   - Testez l'authentification Google
   - Testez les paiements Stripe
   - Vérifiez que les webhooks fonctionnent

#### Notes importantes pour Render :
- Le plan gratuit peut avoir un "spin down" après 15 minutes d'inactivité (première requête peut être lente)
- Pour éviter cela, utilisez un service de "ping" gratuit (ex: UptimeRobot) pour maintenir le service actif
- Ou passez au plan payant pour un service toujours actif

### Option 3 : Autres serveurs web (Production)
- Apache avec mod_php
- Nginx avec PHP-FPM
- **Important : HTTPS obligatoire pour PWA en production**

## 📝 Notes importantes

1. **HTTPS requis** : Les PWA nécessitent HTTPS en production (sauf localhost)
2. **Service Worker** : Doit être dans la racine ou un sous-dossier accessible
3. **Manifest** : Doit être accessible et valide JSON
4. **Icônes** : Minimum 192x192 et 512x512 requis

## 🐛 Dépannage

### L'application ne s'installe pas
- Vérifiez que HTTPS est activé
- Vérifiez la console pour les erreurs
- Assurez-vous que manifest.json est accessible

### Service Worker ne fonctionne pas
- Vérifiez que le fichier est accessible
- Vérifiez la console du navigateur
- Assurez-vous que HTTPS est activé (ou localhost)

### APK ne se génère pas
- Vérifiez que votre PWA est accessible publiquement
- Vérifiez que toutes les icônes sont présentes
- Utilisez PWA Builder pour diagnostiquer les problèmes

## 📚 Ressources

- [PWA Builder](https://www.pwabuilder.com/)
- [MDN - Progressive Web Apps](https://developer.mozilla.org/fr/docs/Web/Progressive_web_apps)
- [Web.dev - PWA](https://web.dev/progressive-web-apps/)

## 📄 Licence

Libre d'utilisation pour vos projets.

# quantanous2025
# quantanous2025
