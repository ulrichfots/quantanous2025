<?php
/**
 * Helper basique pour envoyer des e-mails (reçus Stripe, notifications, etc.).
 * Utilise la fonction mail() de PHP. Prévoir un service SMTP/config serveur en production.
 */
class EmailHelper
{
    private $from;
    private $replyTo;
    private $bcc;
    private $companyName;
    private $smtpHost;
    private $smtpPort;
    private $smtpUser;
    private $smtpPass;
    private $smtpSecure;

    public function __construct()
    {
        if (!file_exists(__DIR__ . '/stripe-config.php')) {
            throw new RuntimeException('Fichier stripe-config.php introuvable pour la configuration e-mail.');
        }

        $config = require __DIR__ . '/stripe-config.php';
        $this->from = $config['email_from'] ?? 'no-reply@example.com';
        $this->replyTo = $config['email_reply_to'] ?? null;
        $this->bcc = $config['email_bcc'] ?? null;
        $this->companyName = $config['company_name'] ?? 'Et Tout et Tout';
        
        // Configuration SMTP depuis les variables d'environnement
        $this->smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $this->smtpPort = (int)(getenv('SMTP_PORT') ?: 587);
        $this->smtpUser = getenv('SMTP_USER') ?: '';
        $this->smtpPass = getenv('SMTP_PASS') ?: '';
        $this->smtpSecure = getenv('SMTP_SECURE') ?: 'tls'; // 'tls' ou 'ssl'
    }

    /**
     * Envoie un e-mail multipart (texte + HTML) via SMTP.
     */
    public function sendEmail(string $to, string $subject, string $htmlBody, ?string $textBody = null): bool
    {
        if (empty($to)) {
            error_log('EmailHelper: adresse destinataire manquante.');
            return false;
        }

        // Si SMTP n'est pas configuré, essayer mail() en fallback
        if (empty($this->smtpUser) || empty($this->smtpPass)) {
            error_log('EmailHelper: SMTP non configuré, tentative avec mail()');
            return $this->sendEmailViaMail($to, $subject, $htmlBody, $textBody);
        }

        return $this->sendEmailViaSMTP($to, $subject, $htmlBody, $textBody);
    }

    /**
     * Envoie un email via SMTP.
     */
    private function sendEmailViaSMTP(string $to, string $subject, string $htmlBody, ?string $textBody = null): bool
    {
        try {
            $boundary = md5(uniqid((string) microtime(true), true));

            if ($textBody === null || $textBody === '') {
                $textBody = strip_tags(preg_replace('/<br\s*\/?>(?=.)/i', "\n", $htmlBody));
            }

            $message  = "--{$boundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n" . $textBody . "\r\n";
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n" . $htmlBody . "\r\n";
            $message .= "--{$boundary}--";

            $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8');
            $fromName = $this->companyName;
            $fromEmail = $this->from;

            // Connexion SMTP
            $host = $this->smtpHost;
            $port = $this->smtpPort;
            $secure = $this->smtpSecure === 'ssl' ? 'ssl://' : '';
            
            $socket = @fsockopen($secure . $host, $port, $errno, $errstr, 30);
            if (!$socket) {
                error_log("EmailHelper SMTP: Échec de connexion à {$host}:{$port} - {$errstr} ({$errno})");
                return false;
            }

            $response = fgets($socket, 515);
            if (substr($response, 0, 3) !== '220') {
                error_log("EmailHelper SMTP: Réponse serveur inattendue: {$response}");
                fclose($socket);
                return false;
            }

            // EHLO
            fputs($socket, "EHLO " . $host . "\r\n");
            $response = '';
            while ($line = fgets($socket, 515)) {
                $response .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }

            // STARTTLS si TLS
            if ($this->smtpSecure === 'tls') {
                fputs($socket, "STARTTLS\r\n");
                $response = fgets($socket, 515);
                if (substr($response, 0, 3) !== '220') {
                    error_log("EmailHelper SMTP: STARTTLS échoué: {$response}");
                    fclose($socket);
                    return false;
                }
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                fputs($socket, "EHLO " . $host . "\r\n");
                $response = '';
                while ($line = fgets($socket, 515)) {
                    $response .= $line;
                    if (substr($line, 3, 1) === ' ') break;
                }
            }

            // Authentification
            fputs($socket, "AUTH LOGIN\r\n");
            $response = fgets($socket, 515);
            if (substr($response, 0, 3) !== '334') {
                error_log("EmailHelper SMTP: AUTH LOGIN refusé: {$response}");
                fclose($socket);
                return false;
            }

            fputs($socket, base64_encode($this->smtpUser) . "\r\n");
            $response = fgets($socket, 515);
            if (substr($response, 0, 3) !== '334') {
                error_log("EmailHelper SMTP: Nom d'utilisateur refusé");
                fclose($socket);
                return false;
            }

            fputs($socket, base64_encode($this->smtpPass) . "\r\n");
            $response = fgets($socket, 515);
            if (substr($response, 0, 3) !== '235') {
                error_log("EmailHelper SMTP: Authentification échouée: {$response}");
                fclose($socket);
                return false;
            }

            // MAIL FROM
            fputs($socket, "MAIL FROM: <{$fromEmail}>\r\n");
            $response = fgets($socket, 515);
            if (substr($response, 0, 3) !== '250') {
                error_log("EmailHelper SMTP: MAIL FROM échoué: {$response}");
                fclose($socket);
                return false;
            }

            // RCPT TO
            fputs($socket, "RCPT TO: <{$to}>\r\n");
            $response = fgets($socket, 515);
            if (substr($response, 0, 3) !== '250') {
                error_log("EmailHelper SMTP: RCPT TO échoué: {$response}");
                fclose($socket);
                return false;
            }

            // DATA
            fputs($socket, "DATA\r\n");
            $response = fgets($socket, 515);
            if (substr($response, 0, 3) !== '354') {
                error_log("EmailHelper SMTP: DATA refusé: {$response}");
                fclose($socket);
                return false;
            }

            // Headers
            $headers = "From: {$fromName} <{$fromEmail}>\r\n";
            $headers .= "To: <{$to}>\r\n";
            if (!empty($this->replyTo)) {
                $headers .= "Reply-To: {$this->replyTo}\r\n";
            }
            if (!empty($this->bcc)) {
                $headers .= "Bcc: {$this->bcc}\r\n";
            }
            $headers .= "Subject: {$encodedSubject}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $headers .= "\r\n";

            fputs($socket, $headers . $message . "\r\n.\r\n");
            $response = fgets($socket, 515);
            if (substr($response, 0, 3) !== '250') {
                error_log("EmailHelper SMTP: Envoi échoué: {$response}");
                fclose($socket);
                return false;
            }

            // QUIT
            fputs($socket, "QUIT\r\n");
            fclose($socket);

            error_log("EmailHelper SMTP: Email envoyé avec succès à {$to}");
            return true;

        } catch (Throwable $e) {
            error_log("EmailHelper SMTP: Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fallback: envoie un email via mail() (si disponible).
     */
    private function sendEmailViaMail(string $to, string $subject, string $htmlBody, ?string $textBody = null): bool
    {
        $boundary = md5(uniqid((string) microtime(true), true));

        $headers = "From: {$this->from}\r\n";
        if (!empty($this->replyTo)) {
            $headers .= "Reply-To: {$this->replyTo}\r\n";
        }
        if (!empty($this->bcc)) {
            $headers .= "Bcc: {$this->bcc}\r\n";
        }
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";

        if ($textBody === null || $textBody === '') {
            $textBody = strip_tags(preg_replace('/<br\s*\/?>(?=.)/i', "\n", $htmlBody));
        }

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n" . $textBody . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n" . $htmlBody . "\r\n";
        $body .= "--{$boundary}--";

        $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8');
        $sent = @mail($to, $encodedSubject, $body, $headers);

        if (!$sent) {
            error_log('EmailHelper: échec de l\'envoi du mail à ' . $to);
        }

        return $sent;
    }

    /**
     * Génère et envoie un reçu personnalisé.
     */
    public function sendReceipt(array $data): bool
    {
        $to = $data['to'] ?? '';
        $type = $data['type'] ?? 'paiement';
        $amount = isset($data['amount']) ? (float) $data['amount'] : 0.0;
        $currency = strtoupper($data['currency'] ?? 'EUR');
        $frequency = $data['frequency'] ?? '';
        $metadata = $data['metadata'] ?? [];
        $lineItems = $data['line_items'] ?? [];
        $periodStart = $data['period_start'] ?? null;
        $periodEnd = $data['period_end'] ?? null;

        $frequencyInfo = $this->resolveFrequency($frequency, $metadata);
        $customerName = $data['customer']['name'] ?? $this->buildName($metadata);
        $subject = $this->buildSubject($type, $frequencyInfo);
        $formattedAmount = number_format($amount, 2, ',', ' ') . ' ' . strtoupper($currency);

        $html = $this->buildHtmlBody([
            'name' => $customerName,
            'amount' => $formattedAmount,
            'type' => $type,
            'frequency_info' => $frequencyInfo,
            'metadata' => $metadata,
            'line_items' => $lineItems,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);

        $text = $this->buildTextBody($html);

        return $this->sendEmail($to, $subject, $html, $text);
    }

    private function buildSubject(string $type, array $frequencyInfo = []): string
    {
        $label = $frequencyInfo['label'] ?? '';
        switch ($type) {
            case 'don_regulier':
                return $label
                    ? "Merci pour votre don {$label}"
                    : "Merci pour votre don régulier";
            case 'don_ponctuel':
                return "Merci pour votre don";
            case 'achat':
                return "Confirmation de votre achat";
            default:
                return "Confirmation de paiement";
        }
    }

    private function buildHtmlBody(array $data): string
    {
        $heading = $this->companyName;
        $name = !empty($data['name']) ? htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8') : 'Bonjour';
        $amount = htmlspecialchars($data['amount'], ENT_QUOTES, 'UTF-8');
        $type = $data['type'] ?? 'paiement';
        $frequencyInfo = $data['frequency_info'] ?? [];
        $lineItems = $data['line_items'];

        $intro = $this->introMessage($type, $frequencyInfo, $amount);
        $detailsHtml = $this->lineItemsTable($lineItems, $amount);
        $periodHtml = $this->periodHtml($data['period_start'], $data['period_end']);

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>{$heading}</title>
</head>
<body style="font-family: Arial, sans-serif; color:#333; line-height:1.6;">
    <h2 style="color:#2E7D32;">{$heading}</h2>
    <p>{$name},</p>
    <p>{$intro}</p>
    {$periodHtml}
    {$detailsHtml}
    <p>Ces informations sont également disponibles dans votre relevé bancaire.</p>
    <p>Merci pour votre confiance,<br>L’équipe {$heading}</p>
</body>
</html>
HTML;
    }

    private function buildTextBody(string $html): string
    {
        $text = strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], "\n", $html));
        return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    }

    private function introMessage(string $type, array $frequencyInfo, string $amount): string
    {
        $label = $frequencyInfo['label'] ?? '';
        $capitalized = $frequencyInfo['capitalized'] ?? '';
        $sentence = $frequencyInfo['sentence'] ?? '';

        switch ($type) {
            case 'don_regulier':
                if ($capitalized && $sentence) {
                    return "Nous confirmons la mise en place de votre don {$label} de {$amount}. Il sera prélevé automatiquement {$sentence}, à la même date que ce premier paiement.";
                }
                return "Nous confirmons la mise en place de votre don régulier de {$amount}.";
            case 'don_ponctuel':
                return "Nous confirmons la réception de votre don de {$amount}. Un grand merci pour votre soutien !";
            case 'achat':
                return "Nous confirmons votre paiement de {$amount}. Vous trouverez le détail de votre commande ci-dessous.";
            default:
                return "Nous confirmons la réception de votre paiement de {$amount}.";
        }
    }

    private function lineItemsTable(array $lineItems, string $amount): string
    {
        if (empty($lineItems)) {
            return '';
        }

        $rows = '';
        foreach ($lineItems as $item) {
            $name = htmlspecialchars($item['description'] ?? $item['price']['product'] ?? 'Article', ENT_QUOTES, 'UTF-8');
            $quantity = (int) ($item['quantity'] ?? 1);
            $total = isset($item['amount_total']) ? number_format($item['amount_total'] / 100, 2, ',', ' ') : $amount;
            $rows .= "<tr><td style=\"padding:6px;border:1px solid #ddd;\">{$name}</td><td style=\"padding:6px;border:1px solid #ddd;text-align:center;\">{$quantity}</td><td style=\"padding:6px;border:1px solid #ddd;text-align:right;\">{$total}</td></tr>";
        }

        return <<<HTML
<table style="border-collapse:collapse;width:100%;margin:20px 0;">
    <thead>
        <tr style="background-color:#f5f5f5;">
            <th style="padding:8px;border:1px solid #ddd;text-align:left;">Article</th>
            <th style="padding:8px;border:1px solid #ddd;text-align:center;">Qté</th>
            <th style="padding:8px;border:1px solid #ddd;text-align:right;">Montant</th>
        </tr>
    </thead>
    <tbody>
        {$rows}
    </tbody>
</table>
HTML;
    }

    private function periodHtml(?int $periodStart, ?int $periodEnd): string
    {
        if (!$periodStart || !$periodEnd) {
            return '';
        }

        $start = date('d/m/Y', $periodStart);
        $end = date('d/m/Y', $periodEnd);
        return "<p>Période concernée : du {$start} au {$end}.</p>";
    }

    private function buildName(array $metadata): string
    {
        $prenom = $metadata['prenom'] ?? '';
        $nom = $metadata['nom'] ?? '';
        $fullName = trim($prenom . ' ' . $nom);
        return $fullName !== '' ? $fullName : '';
    }

    private function resolveFrequency(string $frequency, array $metadata): array
    {
        $label = $metadata['frequency_label'] ?? $frequency ?? '';
        $interval = $metadata['frequency_interval'] ?? '';
        $intervalCount = $metadata['frequency_interval_count'] ?? null;

        if (!$label && $interval) {
            $label = $this->labelFromInterval($interval, $intervalCount);
        }

        $normalized = mb_strtolower((string) $label, 'UTF-8');

        $map = [
            'mensuel' => ['label' => 'mensuel', 'capitalized' => 'Mensuel', 'cadence' => 'mois'],
            'trimestriel' => ['label' => 'trimestriel', 'capitalized' => 'Trimestriel', 'cadence' => 'trimestre'],
            'semestriel' => ['label' => 'semestriel', 'capitalized' => 'Semestriel', 'cadence' => 'semestre'],
            'annuel' => ['label' => 'annuel', 'capitalized' => 'Annuel', 'cadence' => 'année'],
            'hebdomadaire' => ['label' => 'hebdomadaire', 'capitalized' => 'Hebdomadaire', 'cadence' => 'semaine'],
            'quotidien' => ['label' => 'quotidien', 'capitalized' => 'Quotidien', 'cadence' => 'jour'],
        ];

        $resolved = $map[$normalized] ?? $map['mensuel'];

        $sentence = 'chaque ' . $resolved['cadence'];
        if ($resolved['cadence'] === 'jour') {
            $sentence = 'chaque jour';
        } elseif ($resolved['cadence'] === 'année') {
            $sentence = 'chaque année';
        } elseif ($resolved['cadence'] === 'semaine') {
            $sentence = 'chaque semaine';
        }

        return array_merge($resolved, [
            'sentence' => $sentence,
            'raw' => $normalized,
        ]);
    }

    private function labelFromInterval(string $interval, $count = null): string
    {
        $interval = strtolower($interval);
        $count = is_numeric($count) ? (int) $count : null;

        if ($interval === 'year') {
            return 'annuel';
        }

        if ($interval === 'month') {
            if ($count === 3) {
                return 'trimestriel';
            }
            if ($count === 6) {
                return 'semestriel';
            }
            return 'mensuel';
        }

        if ($interval === 'week') {
            return 'hebdomadaire';
        }

        if ($interval === 'day') {
            return 'quotidien';
        }

        return 'mensuel';
    }
}
