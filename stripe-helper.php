<?php
/**
 * Helper pour interagir avec l'API Stripe sans dépendances externes.
 */
class StripeHelper
{
    private $secretKey;
    private $publishableKey;
    private $currency;
    private $shippingFee;
    private $baseUrl;
    private $successPath;
    private $cancelPath;
    private $webhookSecret;

    public function __construct()
    {
        if (!file_exists(__DIR__ . '/stripe-config.php')) {
            throw new RuntimeException('Fichier de configuration Stripe introuvable.');
        }

        $config = require __DIR__ . '/stripe-config.php';

        $this->publishableKey = $config['publishable_key'] ?? '';
        $this->secretKey = $config['secret_key'] ?? '';
        $this->currency = $config['currency'] ?? 'eur';
        $this->shippingFee = isset($config['shipping_fee']) ? (float) $config['shipping_fee'] : 0.0;
        $this->baseUrl = $config['base_url'] ?? null;
        $this->successPath = $config['success_path'] ?? '/';
        $this->cancelPath = $config['cancel_path'] ?? '/';
        $this->webhookSecret = $config['webhook_secret'] ?? '';

        if (empty($this->secretKey)) {
            throw new RuntimeException('Clé secrète Stripe manquante.');
        }
    }

    public function getPublishableKey(): string
    {
        return $this->publishableKey;
    }

    public function getDefaultShippingFee(): float
    {
        return $this->shippingFee;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getWebhookSecret(): string
    {
        return $this->webhookSecret;
    }

    /**
     * Crée une session de paiement ou d'abonnement Stripe Checkout.
     */
    public function createCheckoutSession(array $data): array
    {
        $amount = isset($data['amount']) ? (float) $data['amount'] : 0;
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Montant invalide'];
        }

        $mode = $data['mode'] ?? 'payment';
        $shippingFee = isset($data['shipping_fee']) ? (float) $data['shipping_fee'] : $this->shippingFee;

        if ($mode === 'subscription') {
            $shippingFee = 0.0;
        }

        $totalAmount = $mode === 'payment' ? $amount + $shippingFee : $amount;
        $amountInCents = (int) round($totalAmount * 100);

        $productName = $data['product_name'] ?? 'Paiement quantanous';
        $metadata = $data['metadata'] ?? [];

        $postData = [
            'mode' => $mode,
            'payment_method_types[0]' => 'card',
            'success_url' => $this->buildUrl($this->successPath),
            'cancel_url' => $this->buildUrl($this->cancelPath),
            'line_items[0][price_data][currency]' => $this->currency,
            'line_items[0][price_data][product_data][name]' => $productName,
            'line_items[0][price_data][unit_amount]' => $amountInCents,
            'line_items[0][quantity]' => 1,
        ];

        if (!empty($data['customer_email'])) {
            $postData['customer_email'] = $data['customer_email'];
        }

        if (!empty($data['description'])) {
            $postData['line_items[0][price_data][product_data][description]'] = $data['description'];
        }

        if ($mode === 'subscription') {
            $postData['line_items[0][price_data][recurring][interval]'] = $data['recurring_interval'] ?? 'month';
            if (!empty($data['recurring_interval_count'])) {
                $postData['line_items[0][price_data][recurring][interval_count]'] = (int) $data['recurring_interval_count'];
            }
        }

        foreach ($metadata as $key => $value) {
            if ($value !== null && $value !== '') {
                $postData["metadata[$key]"] = $value;
                if ($mode === 'subscription') {
                    $postData["subscription_data[metadata][$key]"] = $value;
                }
            }
        }

        if ($mode === 'payment') {
            $postData['metadata[base_amount]'] = number_format($amount, 2, '.', '');
            if ($shippingFee > 0) {
                $postData['metadata[shipping_fee]'] = number_format($shippingFee, 2, '.', '');
            }
        }

        $response = $this->makeRequest('https://api.stripe.com/v1/checkout/sessions', $postData);

        if (isset($response['error'])) {
            return ['success' => false, 'error' => $response['error']];
        }

        return ['success' => true, 'data' => $response];
    }

    /**
     * Vérifie la signature Stripe d'un webhook.
     */
    public function verifyWebhookSignature(string $payload, string $signatureHeader, int $tolerance = 300): bool
    {
        if (empty($this->webhookSecret)) {
            throw new RuntimeException('Secret de webhook Stripe manquant.');
        }
        if (empty($signatureHeader)) {
            return false;
        }

        $signatures = $this->parseSignatureHeader($signatureHeader);
        if (!isset($signatures['t']) || empty($signatures['v1'])) {
            return false;
        }

        $timestamp = (int) $signatures['t'];
        if ($tolerance > 0 && abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        foreach ($signatures['v1'] as $signature) {
            if (hash_equals($expectedSignature, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Récupère une session Checkout (avec possibilité d'élargir).
     */
    public function retrieveCheckoutSession(string $sessionId, bool $withLineItems = false): array
    {
        $url = 'https://api.stripe.com/v1/checkout/sessions/' . urlencode($sessionId);
        $params = [];
        if ($withLineItems) {
            $params['expand[]'] = 'line_items';
        }

        $response = $this->makeGetRequest($url, $params);
        if (isset($response['error'])) {
            return ['success' => false, 'error' => $response['error'], 'data' => null];
        }

        return ['success' => true, 'data' => $response];
    }

    /**
     * Récupère une subscription Stripe.
     */
    public function retrieveSubscription(string $subscriptionId): array
    {
        $url = 'https://api.stripe.com/v1/subscriptions/' . urlencode($subscriptionId);
        $response = $this->makeGetRequest($url);
        if (isset($response['error'])) {
            return ['success' => false, 'error' => $response['error'], 'data' => null];
        }

        return ['success' => true, 'data' => $response];
    }

    /**
     * Crée un Payment Intent pour un paiement intégré (sans redirection).
     */
    public function createPaymentIntent(array $data): array
    {
        $amount = isset($data['amount']) ? (float) $data['amount'] : 0;
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Montant invalide'];
        }

        $shippingFee = isset($data['shipping_fee']) ? (float) $data['shipping_fee'] : 0.0;
        $totalAmount = $amount + $shippingFee;
        $amountInCents = (int) round($totalAmount * 100);

        $metadata = $data['metadata'] ?? [];
        $customerEmail = $data['customer_email'] ?? null;

        $postData = [
            'amount' => $amountInCents,
            'currency' => $this->currency,
            'payment_method_types[0]' => 'card',
            'automatic_payment_methods[enabled]' => 'true',
        ];

        if ($customerEmail) {
            $postData['receipt_email'] = $customerEmail;
        }

        foreach ($metadata as $key => $value) {
            if ($value !== null && $value !== '') {
                $postData["metadata[$key]"] = $value;
            }
        }

        $postData['metadata[base_amount]'] = number_format($amount, 2, '.', '');
        if ($shippingFee > 0) {
            $postData['metadata[shipping_fee]'] = number_format($shippingFee, 2, '.', '');
        }

        $response = $this->makeRequest('https://api.stripe.com/v1/payment_intents', $postData);

        if (isset($response['error'])) {
            return ['success' => false, 'error' => $response['error']];
        }

        return ['success' => true, 'data' => $response];
    }

    /**
     * Confirme un Payment Intent avec un Payment Method.
     */
    public function confirmPaymentIntent(string $paymentIntentId, string $paymentMethodId): array
    {
        $postData = [
            'payment_method' => $paymentMethodId,
        ];

        $response = $this->makeRequest(
            'https://api.stripe.com/v1/payment_intents/' . urlencode($paymentIntentId) . '/confirm',
            $postData
        );

        if (isset($response['error'])) {
            return ['success' => false, 'error' => $response['error']];
        }

        return ['success' => true, 'data' => $response];
    }

    /**
     * Crée un Setup Intent pour un abonnement (don récurrent).
     */
    public function createSetupIntent(array $data): array
    {
        $customerEmail = $data['customer_email'] ?? null;
        $metadata = $data['metadata'] ?? [];

        $postData = [
            'payment_method_types[0]' => 'card',
        ];

        if ($customerEmail) {
            $postData['customer_email'] = $customerEmail;
        }

        foreach ($metadata as $key => $value) {
            if ($value !== null && $value !== '') {
                $postData["metadata[$key]"] = $value;
            }
        }

        $response = $this->makeRequest('https://api.stripe.com/v1/setup_intents', $postData);

        if (isset($response['error'])) {
            return ['success' => false, 'error' => $response['error']];
        }

        return ['success' => true, 'data' => $response];
    }

    /**
     * Crée une Subscription Stripe pour un don récurrent.
     */
    public function createSubscription(array $data): array
    {
        $amount = isset($data['amount']) ? (float) $data['amount'] : 0;
        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Montant invalide'];
        }

        $paymentMethodId = $data['payment_method_id'] ?? '';
        $customerEmail = $data['customer_email'] ?? null;
        $interval = $data['interval'] ?? 'month';
        $intervalCount = isset($data['interval_count']) ? (int) $data['interval_count'] : 1;
        $metadata = $data['metadata'] ?? [];

        $amountInCents = (int) round($amount * 100);

        // Créer ou récupérer le customer
        $customerId = null;
        if ($customerEmail) {
            // Créer un nouveau customer (Stripe gère automatiquement les doublons si nécessaire)
            $customerData = [
                'email' => $customerEmail,
            ];
            $customerResponse = $this->makeRequest('https://api.stripe.com/v1/customers', $customerData);
            if (!isset($customerResponse['error'])) {
                $customerId = $customerResponse['id'] ?? null;
            } else {
                // Si erreur, essayer de récupérer un customer existant
                $searchResponse = $this->makeGetRequest('https://api.stripe.com/v1/customers', ['email' => $customerEmail, 'limit' => 1]);
                if (isset($searchResponse['data']) && count($searchResponse['data']) > 0) {
                    $customerId = $searchResponse['data'][0]['id'] ?? null;
                }
            }
        }
        
        if (!$customerId) {
            return ['success' => false, 'error' => 'Impossible de créer ou récupérer le customer'];
        }
        
        // Attacher le payment method au customer
        if ($paymentMethodId) {
            $attachData = [
                'customer' => $customerId,
            ];
            $attachResponse = $this->makeRequest(
                'https://api.stripe.com/v1/payment_methods/' . urlencode($paymentMethodId) . '/attach',
                $attachData
            );
            if (isset($attachResponse['error'])) {
                return ['success' => false, 'error' => 'Erreur lors de l\'attachement du moyen de paiement: ' . $attachResponse['error']];
            }
        }

        // Créer le produit et le prix
        $productName = $data['product_name'] ?? 'Don récurrent - quantanous';
        $priceData = [
            'product_data[name]' => $productName,
            'unit_amount' => $amountInCents,
            'currency' => $this->currency,
            'recurring[interval]' => $interval,
            'recurring[interval_count]' => $intervalCount,
        ];

        $priceResponse = $this->makeRequest('https://api.stripe.com/v1/prices', $priceData);
        if (isset($priceResponse['error'])) {
            return ['success' => false, 'error' => $priceResponse['error']];
        }
        $priceId = $priceResponse['id'] ?? null;

        if (!$priceId) {
            return ['success' => false, 'error' => 'Impossible de créer le prix'];
        }

        // Créer la subscription
        $subscriptionData = [
            'customer' => $customerId,
            'items[0][price]' => $priceId,
            'payment_settings[payment_method_types][0]' => 'card',
            'expand[]' => 'latest_invoice.payment_intent',
        ];

        if ($paymentMethodId) {
            $subscriptionData['default_payment_method'] = $paymentMethodId;
        }

        foreach ($metadata as $key => $value) {
            if ($value !== null && $value !== '') {
                $subscriptionData["metadata[$key]"] = $value;
            }
        }

        $subscriptionResponse = $this->makeRequest('https://api.stripe.com/v1/subscriptions', $subscriptionData);

        if (isset($subscriptionResponse['error'])) {
            return ['success' => false, 'error' => $subscriptionResponse['error']];
        }

        return ['success' => true, 'data' => $subscriptionResponse];
    }

    private function makeRequest(string $url, array $postData): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array_filter($postData, fn($value) => $value !== null)));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        curl_setopt($ch, CURLOPT_USERPWD, $this->secretKey . ':');

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => $error];
        }

        $decoded = json_decode($response, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $message = $decoded['error']['message'] ?? 'Erreur inconnue Stripe';
            return ['error' => $message, 'stripe_response' => $decoded];
        }

        return $decoded;
    }

    private function makeGetRequest(string $url, array $query = []): array
    {
        $queryString = $query ? '?' . http_build_query($query) : '';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . $queryString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        curl_setopt($ch, CURLOPT_USERPWD, $this->secretKey . ':');

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => $error];
        }

        $decoded = json_decode($response, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $message = $decoded['error']['message'] ?? 'Erreur inconnue Stripe';
            return ['error' => $message, 'stripe_response' => $decoded];
        }

        return $decoded;
    }

    private function parseSignatureHeader(string $header): array
    {
        $parts = explode(',', $header);
        $result = ['v1' => []];

        foreach ($parts as $part) {
            [$key, $value] = array_map('trim', explode('=', $part, 2));
            if ($key === 't') {
                $result['t'] = $value;
            } elseif ($key === 'v1') {
                $result['v1'][] = $value;
            }
        }

        return $result;
    }

    private function buildUrl(string $path): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $baseUrl = $this->baseUrl;
        if (!$baseUrl) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $baseUrl = $scheme . '://' . $host;
        }

        return rtrim($baseUrl, '/') . $path;
    }
}
