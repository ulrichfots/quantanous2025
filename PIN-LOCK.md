# Verrouillage par PIN

## Fonctionnement général
- Le code PIN protège uniquement les pages d’administration (`admin-*`).
- Le menu contextuel reste accessible sans code, mais dès qu’on ouvre une page d’administration, un écran PIN s’affiche.
- Le PIN est redemandé **à chaque accès** : aucune validation n’est conservée d’une page à l’autre.

## Flux utilisateur
1. Depuis le menu, choisir par exemple « Modifier la présentation ».
2. Une page dédiée demande immédiatement le code (271244 par défaut).
3. Après validation, la page d’administration se recharge. Dès que l’on change de page admin, le PIN est à nouveau requis.
4. Le même flux s’applique à « Modifier les achats », « Modifier les explications » et « Modifier le code PIN ».

## Actions côté API
- `POST /api.php/verify-pin` : vérifie le PIN (`{"pin":"271244"}`) et autorise l’accès courant.
- `POST /api.php/save-pin` : change le PIN (l’utilisateur est automatiquement re-authentifié avec le nouveau code pour la page en cours).

## Données Back4App
- Classe `Pin` : doit contenir un champ `pin` (String). Si la classe est vide, le code par défaut `271244` est utilisé.

## À tester
1. Ouvrir « Modifier la présentation » → la page PIN apparaît.
2. Entrer un code erroné → message d’erreur.
3. Entrer `271244` (ou le PIN enregistré) → la page s’affiche.
4. Se rendre ensuite sur « Modifier les achats » → le PIN est redemandé.
5. Changer le PIN via « Modifier le code PIN » puis vérifier que l’ancien code échoue.

## Personnalisation
- Texte / styles de la page PIN : `pin-gate.php`, `pin-modal.php`, section « Verrouillage PIN » dans `assets/css/style.css`.
- Logique : fonctions `pin_mark_validated`, `pin_is_validated`, `pin_require` dans `auth.php`.
