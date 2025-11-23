# Webhooks Stripe & Reçus e-mail

Ce projet envoie désormais un e-mail personnalisé après chaque paiement Stripe (don ponctuel, don mensuel, achat) grâce à un webhook.

## 1. Prérequis

1. **Ngrok ou URL publique** : le serveur local doit être joignable par Stripe (ex. `https://xxxx.ngrok.app/api.php/stripe-webhook`).
2. **Serveur mail** : la fonction `mail()` de PHP doit être opérationnelle (config sendmail/SMTP) pour l’envoi des e-mails.
3. **Clés Stripe** : `stripe-config.php` doit contenir vos clés test ou live.

## 2. Configuration côté Stripe

1. Ouvrez le [Dashboard Stripe](https://dashboard.stripe.com/).
2. Dans le menu **Developers → Webhooks**, cliquez sur **Add endpoint**.
3. URL du webhook : `https://votre-domaine/api.php/stripe-webhook`
4. Sélectionnez au minimum les événements :
   - `checkout.session.completed`
   - `invoice.payment_succeeded`
5. Sauvegardez puis copiez le **Signing secret** (`whsec_...`).
6. Collez-le dans `stripe-config.php` :
   ```php
   'webhook_secret' => 'whsec_XXXXXXXX',
   ```

> ⚠️ En mode développement, utilisez un tunnel (Ngrok, Cloudflared…) vers votre serveur local.

## 3. Configuration des e-mails

Toujours dans `stripe-config.php`, renseignez :

```php
'company_name'  => 'quantanous',
'email_from'    => 'no-reply@votredomaine.fr',
'email_reply_to'=> 'contact@votredomaine.fr', // optionnel
'email_bcc'     => 'tresorier@votredomaine.fr', // optionnel
```

## 4. Tests

1. Lancez le serveur PHP : `php -S localhost:8000`
2. Démarrez votre tunnel (ex. `ngrok http 8000`).
3. Mettez à jour l’URL du webhook dans Stripe avec l’adresse fournie par le tunnel.
4. Sur Stripe, utilisez le bouton **Send test webhook** et choisissez `checkout.session.completed`.
5. Vérifiez les logs PHP : vous devriez voir une trace d’e-mail (ou le mail reçu si votre serveur est configuré).

Pour un test complet :
- effectuez un don ponctuel et un don régulier avec une carte de test (`4242 4242 4242 4242`).
- vérifiez que l’e-mail est reçu après le paiement.
- pour l’abonnement, Stripe enverra ensuite des événements `invoice.payment_succeeded` à chaque cycle ; un e-mail automatique sera renvoyé.

## 5. Dépannage

### « Signature Stripe invalide »
- Vérifiez que le `webhook_secret` correspond bien au secret affiché dans Stripe.
- Assurez-vous que votre tunnel (Ngrok, Cloudflared…) est actif.

### Je ne reçois pas d’e-mail
- Confirmez que `mail()` fonctionne sur votre environnement (sinon utilisez un service SMTP/SendGrid/Mailgun).
- Vérifiez le dossier spam.
- Consultez les logs PHP (recherche de `EmailHelper`).

### Stripe renvoie plusieurs fois le webhook
- Stripe réessaie si le serveur ne répond pas avec un `200`. Les réponses JSON `{"status":"success"}` indiquent que tout s’est bien passé.

## 6. Personnalisation

- Vous pouvez adapter le template HTML dans `email-helper.php` (`buildHtmlBody`).
- Pour stocker des reçus, interceptez les requêtes dans `/stripe-webhook` (fichier `api.php`) et sauvegardez les informations dans Back4app.
