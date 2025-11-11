# Guide Complet : Créer les Classes Back4app

## Étape 1 : Accéder à la Base de Données

1. Connectez-vous à votre compte Back4app : https://www.back4app.com
2. Cliquez sur votre application **"quantanous"**
3. Dans le menu de gauche, cliquez sur **"Database"**

## Étape 2 : Créer la Classe "Content"

Cette classe stockera les textes (présentation et explications).

### 2.1 Créer la classe

1. Cliquez sur le bouton **"Create a class"** (en haut à droite)
2. Dans le champ "Class name", entrez : `Content`
3. Cliquez sur **"Create"**

### 2.2 Ajouter les champs

Une fois la classe créée, ajoutez les champs suivants en cliquant sur **"Add a new column"** :

| Nom du champ | Type | Options |
|-------------|------|---------|
| `type` | String | Requis : Oui |
| `content` | String | Requis : Oui |
| `updated_at` | String | Requis : Non |

**Note :** Les champs seront créés automatiquement lors de la première sauvegarde, mais il est recommandé de les créer manuellement.

### 2.3 Configurer les permissions

1. Cliquez sur l'onglet **"Security"** (en haut de la page de la classe)
2. Configurez les permissions comme suit :

**Class Level Permissions :**
- **Get** : `public` (tous les utilisateurs peuvent lire)
- **Find** : `public` (tous les utilisateurs peuvent rechercher)
- **Create** : `requiresAuthentication` (seulement via API avec clés)
- **Update** : `requiresAuthentication` (seulement via API avec clés)
- **Delete** : `requiresAuthentication` (seulement via API avec clés)

**Field Level Permissions :**
- Laissez les permissions par défaut (tous les champs accessibles)

## Étape 3 : Créer la Classe "Pin"

Cette classe stockera le code PIN.

### 3.1 Créer la classe

1. Retournez à la liste des classes (cliquez sur "Database" dans le menu)
2. Cliquez sur **"Create a class"**
3. Nom de la classe : `Pin`
4. Cliquez sur **"Create"**

### 3.2 Ajouter les champs

| Nom du champ | Type | Options |
|-------------|------|---------|
| `pin` | String | Requis : Oui |
| `created_at` | String | Requis : Non |
| `updated_at` | String | Requis : Non |

### 3.3 Configurer les permissions (IMPORTANT pour la sécurité)

1. Cliquez sur l'onglet **"Security"**
2. Configurez les permissions de manière **RESTRICTIVE** :

**Class Level Permissions :**
- **Get** : `requiresAuthentication` (seulement via API)
- **Find** : `requiresAuthentication` (seulement via API)
- **Create** : `requiresAuthentication` (seulement via API)
- **Update** : `requiresAuthentication` (seulement via API)
- **Delete** : `requiresAuthentication` (seulement via API)

**⚠️ IMPORTANT :** Ne mettez JAMAIS les permissions en `public` pour la classe Pin, car cela exposerait le code PIN !

## Étape 4 : Créer la Classe "Project"

Cette classe stockera les projets/articles.

### 4.1 Créer la classe

1. Retournez à la liste des classes
2. Cliquez sur **"Create a class"**
3. Nom de la classe : `Project`
4. Cliquez sur **"Create"**

### 4.2 Ajouter les champs

| Nom du champ | Type | Options |
|-------------|------|---------|
| `titre` | String | Requis : Oui |
| `description` | String | Requis : Oui |
| `prix` | Number | Requis : Oui |
| `tva_incluse` | Boolean | Requis : Non (défaut : false) |
| `image` | String | Requis : Non |
| `created_at` | String | Requis : Non |
| `updated_at` | String | Requis : Non |

### 4.3 Configurer les permissions

1. Cliquez sur l'onglet **"Security"**
2. Configurez les permissions :

**Class Level Permissions :**
- **Get** : `public` (tous les utilisateurs peuvent lire)
- **Find** : `public` (tous les utilisateurs peuvent rechercher)
- **Create** : `requiresAuthentication` (seulement via API avec clés)
- **Update** : `requiresAuthentication` (seulement via API avec clés)
- **Delete** : `requiresAuthentication` (seulement via API avec clés)

## Étape 5 : Vérifier les Clés API

Pour que votre application PHP puisse accéder à Back4app, vérifiez que vous avez les bonnes clés :

1. Dans le menu de gauche, cliquez sur **"App Settings"**
2. Allez dans l'onglet **"Security & Keys"**
3. Vérifiez que vous avez :
   - **Application ID** : `7CwmKkSFePdneHYxhudJbScV7XshQgIL1QuO1OQ2`
   - **REST API Key** : `XDhhVSVgPMOC1rDv4IZUGIPFkjoe7HNB9r56wSWj`

4. Si les clés sont différentes, mettez à jour le fichier `back4app-config.php` avec les bonnes valeurs.

## Étape 6 : Tester la Connexion

### 6.1 Test via l'API

Vous pouvez tester la connexion en appelant :
```
GET http://localhost:8000/api.php/test
```

### 6.2 Test de création d'un contenu

Pour tester la sauvegarde d'un texte :
```
POST http://localhost:8000/api.php/save-presentation
Content-Type: application/json

{
  "type": "presentation",
  "content": "Test de contenu"
}
```

### 6.3 Vérifier dans Back4app

1. Retournez dans **"Database"**
2. Cliquez sur la classe **"Content"**
3. Vous devriez voir l'objet créé avec le contenu de test

## Résumé des Permissions

| Classe | Get/Find | Create/Update/Delete |
|--------|----------|---------------------|
| **Content** | Public | Authentification requise |
| **Pin** | Authentification requise | Authentification requise |
| **Project** | Public | Authentification requise |

## Notes Importantes

1. **Sécurité du PIN** : La classe Pin doit TOUJOURS avoir des permissions restrictives. Ne la mettez jamais en public.

2. **Création automatique des champs** : Si vous oubliez de créer un champ, Back4app le créera automatiquement lors de la première sauvegarde. Cependant, il est recommandé de les créer manuellement pour une meilleure organisation.

3. **Format des dates** : Les dates sont stockées au format ISO 8601 (ex: `2024-01-15T10:30:00Z`)

4. **Images** : Les images peuvent être stockées en base64 dans le champ `image` de la classe Project, ou vous pouvez utiliser des URLs vers des images hébergées ailleurs.

## Dépannage

### Erreur "Class does not exist"
- Vérifiez que vous avez bien créé les classes avec les noms exacts : `Content`, `Pin`, `Project` (respectez la casse)

### Erreur "Permission denied"
- Vérifiez les permissions dans l'onglet "Security" de chaque classe
- Assurez-vous que les clés API sont correctes dans `back4app-config.php`

### Erreur "Invalid API key"
- Vérifiez vos clés dans "App Settings" > "Security & Keys"
- Mettez à jour `back4app-config.php` si nécessaire

