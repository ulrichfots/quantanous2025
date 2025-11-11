<?php
/**
 * Configuration Stripe
 * Variables d'environnement attendues :
 *  - STRIPE_PUBLISHABLE_KEY
 *  - STRIPE_SECRET_KEY
 *  - STRIPE_WEBHOOK_SECRET
 *  - STRIPE_SHIPPING_FEE (optionnel, défaut 5.00)
 *  - STRIPE_CURRENCY (optionnel, défaut eur)
 *  - STRIPE_EMAIL_FROM (optionnel)
 *  - STRIPE_EMAIL_REPLY_TO (optionnel)
 *  - STRIPE_EMAIL_BCC (optionnel)
 *  - STRIPE_COMPANY_NAME (optionnel)
 *  - BASE_URL (optionnel, sinon auto)
 */

$publishableKey = getenv('STRIPE_PUBLISHABLE_KEY');
$secretKey = getenv('STRIPE_SECRET_KEY');
$webhookSecret = getenv('STRIPE_WEBHOOK_SECRET');

foreach ([
    'STRIPE_PUBLISHABLE_KEY' => $publishableKey,
    'STRIPE_SECRET_KEY' => $secretKey,
    'STRIPE_WEBHOOK_SECRET' => $webhookSecret,
] as $env => $value) {
    if (!$value) {
        throw new RuntimeException("La variable d'environnement {$env} est manquante.");
    }
}

$shippingFee = getenv('STRIPE_SHIPPING_FEE');
$currency = getenv('STRIPE_CURRENCY') ?: 'eur';
$companyName = getenv('STRIPE_COMPANY_NAME') ?: 'Et Tout et Tout';
$emailFrom = getenv('STRIPE_EMAIL_FROM') ?: 'no-reply@example.com';
$emailReplyTo = getenv('STRIPE_EMAIL_REPLY_TO') ?: null;
$emailBcc = getenv('STRIPE_EMAIL_BCC') ?: null;
$baseUrl = getenv('BASE_URL') ?: null;

return [
    'publishable_key' => $publishableKey,
    'secret_key' => $secretKey,
    'currency' => $currency,
    'shipping_fee' => $shippingFee !== false ? (float) $shippingFee : 5.00,
    'webhook_secret' => $webhookSecret,
    'company_name' => $companyName,
    'email_from' => $emailFrom,
    'email_reply_to' => $emailReplyTo ?: null,
    'email_bcc' => $emailBcc ?: null,
    'base_url' => $baseUrl,
    'success_path' => '/index.php?status=success',
    'cancel_path' => '/paiement.php?status=cancel'
];
