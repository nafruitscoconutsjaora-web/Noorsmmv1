<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class RapydGateway extends AbstractGateway
{
    protected string $code = 'rapyd';
    protected string $name = 'Rapyd';
    protected string $category = 'international';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'EUR', 'GBP', 'SGD', 'BRL', 'MXN'];

    public function getCredentialFields(): array
    {
        return [
            'access_key' => [
                'label' => 'Access Key',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Rapyd Access Key',
                'help' => 'From Rapyd Client Portal.',
            ],
            'secret_key' => [
                'label' => 'Secret Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Rapyd Secret Key',
                'help' => 'Rapyd Secret Key.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (api.rapyd.net)',
                    'sandbox' => 'Sandbox (sandboxapi.rapyd.net)',
                ],
                'help' => 'Rapyd environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'production') === 'sandbox'
            ? 'https://sandboxapi.rapyd.net'
            : 'https://api.rapyd.net';
    }

    private function generateSignature(string $method, string $path, string $salt, string $timestamp, string $accessKey, string $secretKey, string $body = ''): string
    {
        $toSign = strtolower($method) . $path . $salt . $timestamp . $accessKey . $secretKey . $body;
        $hash = hash_hmac('sha256', $toSign, $secretKey);
        return base64_encode($hash);
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $accessKey = trim((string)($creds['access_key'] ?? ''));
        $secretKey = trim((string)($creds['secret_key'] ?? ''));

        if (empty($accessKey) || empty($secretKey)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Rapyd credentials are not configured.',
            ];
        }

        $orderId = 'RPD_' . $paymentData['order_id'];
        $amount = (float)$paymentData['payable_amount'];
        $currency = strtoupper($paymentData['currency'] ?? 'USD');
        $baseUrl = $this->getBaseUrl($creds);
        $path = '/v1/checkout';

        $payload = [
            'amount' => $amount,
            'currency' => $currency,
            'merchant_reference_id' => $orderId,
            'complete_checkout_url' => $paymentData['return_url'],
            'cancel_checkout_url' => $paymentData['cancel_url'],
        ];

        $jsonPayload = json_encode($payload);
        $salt = bin2hex(random_bytes(8));
        $timestamp = (string)time();
        $signature = $this->generateSignature('post', $path, $salt, $timestamp, $accessKey, $secretKey, $jsonPayload);

        $headers = [
            'access_key: ' . $accessKey,
            'salt: ' . $salt,
            'timestamp: ' . $timestamp,
            'signature: ' . $signature,
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}{$path}", $jsonPayload, $headers);

        if ($res['success'] && !empty($res['data']['data']['redirect_url'])) {
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $res['data']['data']['redirect_url'],
                'gateway_order_id' => (string)($res['data']['data']['id'] ?? $orderId),
                'checkout_data' => $res['data']['data'],
                'message' => 'Rapyd checkout initialized.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Rapyd checkout error: ' . ($res['data']['status']['message'] ?? $res['error'] ?? 'Failed to connect'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $checkoutId = (string)$request->input('id', $request->input('checkout_id', ''));
        if (empty($checkoutId)) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'USD',
                'raw_response' => $request->all(),
                'error' => 'Missing Rapyd checkout ID.',
            ];
        }

        $creds = $this->getCredentials($gatewayRow);
        $accessKey = trim((string)($creds['access_key'] ?? ''));
        $secretKey = trim((string)($creds['secret_key'] ?? ''));
        $baseUrl = $this->getBaseUrl($creds);
        $path = "/v1/checkout/{$checkoutId}";

        $salt = bin2hex(random_bytes(8));
        $timestamp = (string)time();
        $signature = $this->generateSignature('get', $path, $salt, $timestamp, $accessKey, $secretKey);

        $headers = [
            'access_key: ' . $accessKey,
            'salt: ' . $salt,
            'timestamp: ' . $timestamp,
            'signature: ' . $signature,
        ];

        $res = $this->httpRequest('GET', "{$baseUrl}{$path}", null, $headers);

        if ($res['success'] && ($res['data']['data']['status'] ?? '') === 'SUCCESS') {
            return [
                'success' => true,
                'gateway_order_id' => $checkoutId,
                'transaction_id' => (string)($res['data']['data']['payment']['id'] ?? $checkoutId),
                'amount' => (string)($res['data']['data']['amount'] ?? '0'),
                'currency' => (string)($res['data']['data']['currency'] ?? 'USD'),
                'raw_response' => $res['data'],
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $checkoutId,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'USD',
            'raw_response' => $res['data'] ?? [],
            'error' => 'Rapyd payment unconfirmed.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
