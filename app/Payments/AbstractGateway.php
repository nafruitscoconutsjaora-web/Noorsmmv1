<?php

declare(strict_types=1);

namespace App\Payments;

use App\Core\Request;
use App\Support\Encryption;
use App\Support\Logger;

abstract class AbstractGateway implements PaymentGatewayInterface
{
    protected string $code;
    protected string $name;
    protected string $category = 'international';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'EUR', 'GBP'];

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getDefaultCurrency(): string
    {
        return $this->defaultCurrency;
    }

    public function getSupportedCurrencies(): array
    {
        return $this->supportedCurrencies;
    }

    /**
     * Parse and decrypt credentials from database row.
     */
    public function getCredentials(array $gatewayRow): array
    {
        $raw = $gatewayRow['credentials'] ?? null;
        if (empty($raw)) {
            return [];
        }

        if (is_array($raw)) {
            return $raw;
        }

        // Try decrypting first
        $decrypted = Encryption::decrypt($raw);
        if ($decrypted !== null) {
            $parsed = json_decode($decrypted, true);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        // Fallback to plain json decode
        $parsed = json_decode($raw, true);
        return is_array($parsed) ? $parsed : [];
    }

    /**
     * Parse gateway configuration.
     */
    public function getConfig(array $gatewayRow): array
    {
        $raw = $gatewayRow['config'] ?? null;
        if (empty($raw)) {
            return [];
        }

        if (is_array($raw)) {
            return $raw;
        }

        $parsed = json_decode($raw, true);
        return is_array($parsed) ? $parsed : [];
    }

    /**
     * Check whether required credential fields are filled and non-empty.
     */
    public function isConfigured(array $credentials, array $config = []): bool
    {
        $fields = $this->getCredentialFields();
        foreach ($fields as $key => $meta) {
            if (!empty($meta['required']) && empty(trim((string)($credentials[$key] ?? '')))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Perform an HTTP request with SSRF guard and error handling.
     */
    protected function httpRequest(
        string $method,
        string $url,
        array|string|null $payload = null,
        array $headers = [],
        int $timeout = 30
    ): array {
        if (!$this->isUrlSafe($url)) {
            return [
                'success' => false,
                'status' => 0,
                'error' => 'Destination endpoint blocked by security policy.',
                'data' => null,
            ];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SMM-Panel-Payments/1.0');

        $upperMethod = strtoupper($method);
        if ($upperMethod === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($payload !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($payload) ? json_encode($payload) : $payload);
            }
        } elseif ($upperMethod !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $upperMethod);
            if ($payload !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($payload) ? json_encode($payload) : $payload);
            }
        }

        $formattedHeaders = [];
        foreach ($headers as $k => $v) {
            $formattedHeaders[] = is_numeric($k) ? $v : "{$k}: {$v}";
        }
        if (!empty($formattedHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
        }

        $responseBody = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false) {
            Logger::error("Payment HTTP request failed for {$this->code}: {$curlError}", [], 'payments');
            return [
                'success' => false,
                'status' => $httpCode,
                'error' => $curlError ?: 'Network connection failure',
                'data' => null,
                'raw' => null,
            ];
        }

        $decoded = json_decode((string)$responseBody, true);

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'status' => $httpCode,
            'data' => $decoded,
            'raw' => (string)$responseBody,
            'error' => $httpCode >= 400 ? "HTTP Error {$httpCode}" : null,
        ];
    }

    /**
     * Ensure URL does not target loopback or private RFC 1918 addresses (SSRF mitigation).
     */
    protected function isUrlSafe(string $url): bool
    {
        $parts = parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        if ($scheme !== 'https' && $scheme !== 'http') {
            return false;
        }

        $host = $parts['host'];
        if (in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'])) {
            return false;
        }

        $ip = gethostbyname($host);
        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            // DNS resolution failure
            return false;
        }

        // Validate that IP is not in private or reserved ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        return true;
    }

    /**
     * Default verify return (fallback).
     */
    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        return [
            'success' => false,
            'gateway_order_id' => '',
            'transaction_id' => '',
            'amount' => '0',
            'currency' => $this->defaultCurrency,
            'raw_response' => $request->all(),
            'error' => 'Verification adapter not implemented for this gateway',
        ];
    }

    /**
     * Default webhook handler (fallback).
     */
    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return [
            'success' => false,
            'gateway_order_id' => '',
            'transaction_id' => '',
            'amount' => '0',
            'currency' => $this->defaultCurrency,
            'status' => 'failed',
            'raw_payload' => $request->all(),
            'error' => 'Webhook adapter not implemented for this gateway',
        ];
    }
}
