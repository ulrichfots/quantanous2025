# Configuration Back4app

L'application utilise Back4app comme base de données pour sauvegarder :
- Les textes (présentation, explications)
- Le code PIN
- Les projets/articles

## Configuration

Les identifiants Back4app sont configurés dans le fichier `back4app-config.php` :

```php
'api_url' => 'https://parseapi.back4app.com',
'application_id' => 'VOTRE_APPLICATION_ID',
'rest_api_key' => 'VOTRE_REST_API_KEY',
```

## Classes Back4app à créer

Dans votre dashboard Back4app, vous devez créer les classes suivantes :

### 1. Classe "Content"
Pour stocker les textes (présentation, explications)

**Champs :**
- `type` (String) - Type de contenu : "presentation" ou "explications"
- `content` (String) - Le contenu HTML ou texte
- `updated_at` (String) - Date de mise à jour (ISO 8601)

### 2. Classe "Pin"
Pour stocker le code PIN

**Champs :**
- `pin` (String) - Le code PIN (6 chiffres)
- `created_at` (String) - Date de création (ISO 8601)
- `updated_at` (String) - Date de mise à jour (ISO 8601)

### 3. Classe "Project"
Pour stocker les projets/articles

**Champs :**
- `titre` (String) - Titre du projet
- `description` (String) - Description du projet
- `prix` (Number) - Prix du projet
- `tva_incluse` (Boolean) - Si la TVA est incluse
- `image` (String) - URL ou base64 de l'image
- `created_at` (String) - Date de création (ISO 8601)
- `updated_at` (String) - Date de mise à jour (ISO 8601)

## Comment créer les classes dans Back4app

1. Connectez-vous à votre dashboard Back4app
2. Sélectionnez votre application "quantanous"
3. Allez dans "Database" dans le menu de gauche
4. Cliquez sur "Create a class"
5. Créez chaque classe avec les champs mentionnés ci-dessus

**Note :** Les champs seront créés automatiquement lors de la première sauvegarde, mais vous pouvez les créer manuellement pour une meilleure organisation.

## Permissions

Assurez-vous que les permissions sont correctement configurées :
- **Content** : Lecture publique, Écriture via API uniquement
- **Pin** : Lecture/Écriture via API uniquement (sécurité)
- **Project** : Lecture publique, Écriture via API uniquement

## Test de la connexion

Pour tester la connexion, vous pouvez appeler :
```
GET /api.php/test
```

Si tout fonctionne, vous devriez recevoir une réponse de succès.

## Migration des données existantes

Si vous aviez des données dans les fichiers locaux (`data/`), vous pouvez les migrer vers Back4app en utilisant les routes API :
- `/api.php/save-presentation` pour migrer les textes
- `/api.php/add-project` pour migrer les projets

