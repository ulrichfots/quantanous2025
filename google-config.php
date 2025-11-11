<?php
/**
 * Configuration Google OAuth
 * Variables d'environnement attendues :
 *  - GOOGLE_CLIENT_ID (requis)
 *  - GOOGLE_ALLOWED_DOMAINS (optionnel, format: "domain1.com,domain2.com")
 *  - GOOGLE_ALLOWED_EMAILS (optionnel, format: "email1@example.com,email2@example.com")
 */

$clientId = getenv('GOOGLE_CLIENT_ID') ?: '22487843169-19gkn88v8oht90s0ptntuh3ln1iiile3.apps.googleusercontent.com';

$allowedDomainsStr = getenv('GOOGLE_ALLOWED_DOMAINS');
$allowedDomains = [];
if ($allowedDomainsStr) {
    $allowedDomains = array_map('trim', explode(',', $allowedDomainsStr));
    $allowedDomains = array_filter($allowedDomains);
}

$allowedEmailsStr = getenv('GOOGLE_ALLOWED_EMAILS');
$allowedEmails = [];
if ($allowedEmailsStr) {
    $allowedEmails = array_map('trim', explode(',', $allowedEmailsStr));
    $allowedEmails = array_filter($allowedEmails);
}

return [
    'client_id' => $clientId,
    'allowed_domains' => $allowedDomains,
    'allowed_emails' => $allowedEmails,
];
