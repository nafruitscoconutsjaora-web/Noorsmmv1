<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class PayUGlobalGateway extends AbstractGateway
{
    protected string $code = 'payu_global';
    protected string $name = 'PayU Global (Europe / LATAM)';
    protected string $category = 'international';
    protected string $defaultCurrency = 'EUR';
    protected array $supportedCurrencies = ['EUR', 'PLN', 'CZK', 'USD', 'BRL'];

    public function getCredentialFields(): array
    {
        return [
            'pos_id' => [
                'label' => 'POS ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter POS ID',
                'help' => 'Assigned Point of Sale ID.',
            ],
            'second_key' => [
                'label' => 'Second Key (MD5 / SHA-256)',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Second Key',
                'help' => 'Signature verification key.',
            ],
            'client_id' => [
                'label' => 'OAuth Client ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter OAuth Client ID',
                'help' => 'OAuth Client ID.',
            ],
            'client_secret' => [
                'label' => 'OAuth Client Secret',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter OAuth Client Secret',
                'help' => 'OAuth Client Secret.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (secure.payu.com)',
                    'sandbox' => 'Sandbox (secure.snd.payu.com)',
                ],
                'help' => 'PayU Global environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'production') === 'sandbox'
            ? 'https://secure.snd.payu.com'
            : 'https://secure.payu.com';
    }

    private function getAccessToken(array $creds): ?string
    {
        $clientId = trim((string)($creds['client_id'] ?? ''));
        $clientSecret = trim((string)($creds['client_secret'] ?? ''));
        $baseUrl = $this->getBaseUrl($creds);

        $payload = [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/pl/standard/user/oauth/authorize", http_build_query($payload), [
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        return $res['success'] && !empty($res['data']['access_token']) ? (string)$res['data']['access_token'] : null;
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $token = $this->getAccessToken($creds);

        if (!$token) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'PayU Global OAuth authentication failed.',
            ];
        }

        $orderId = 'PUG_' . $paymentData['order_id'];
        $currency = strtoupper($paymentData['currency'] ?? 'EUR');
        $totalAmount = (int)round((float)$paymentData['payable_amount'] * 100);
        $baseUrl = $this->getBaseUrl($creds);

        $payload = [
            'notifyUrl' => $paymentData['notify_url'],
            'continueUrl' => $paymentData['return_url'],
            'customerIp' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'merchantPosId' => $creds['pos_id'] ?? '',
            'description' => 'Wallet Deposit #' . $paymentData['order_id'],
            'currencyCode' => $currency,
            'totalAmount' => (string)$totalAmount,
            'extOrderId' => $orderId,
            'buyer' => [
                'email' => $paymentData['customer_email'] ?? 'customer@example.com',
                'firstName' => $paymentData['customer_name'] ?? 'Customer',
            ],
            'products' => [
                [
                    'name' => 'Wallet Deposit',
                    'unitPrice' => (string)$totalAmount,
                    'quantity' => '1',
                ],
            ],
        ];

        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/api/v2_1/orders", $payload, $headers);

        if ($res['success'] && !empty($res['data']['redirectUri'])) {
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $res['data']['redirectUri'],
                'gateway_order_id' => (string)($res['data']['orderId'] ?? $orderId),
                'checkout_data' => $res['data'],
                'message' => 'PayU Global order created.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'PayU Global order initiation error.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $orderId = (string)$request->input('orderId', $request->input('error', ''));
        if (empty($orderId)) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'EUR',
                'raw_response' => $request->all(),
                'error' => 'Missing PayU order identifier.',
            ];
        }

        $creds = $this->getCredentials($gatewayRow);
        $token = $this->getAccessToken($creds);
        $baseUrl = $this->getBaseUrl($creds);

        $res = $this->httpRequest('GET', "{$baseUrl}/api/v2_1/orders/{$orderId}", null, [
            'Authorization: Bearer ' . $token,
        ]);

        if ($res['success'] && in_array(strtoupper($res['data']['orders'][0]['status'] ?? ''), ['COMPLETED', 'SUCCESS'])) {
            $order = $res['data']['orders'][0];
            return [
                'success' => true,
                'gateway_order_id' => $orderId,
                'transaction_id' => (string)($order['orderId'] ?? $orderId),
                'amount' => (string)(((float)($order['totalAmount'] ?? 0)) / 100),
                'currency' => (string)($order['currencyCode'] ?? 'EUR'),
                'raw_response' => $res['data'],
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $orderId,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'EUR',
            'raw_response' => $res['data'] ?? [],
            'error' => 'Payment status: ' . ($res['data']['orders'][0]['status'] ?? 'pending'),
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
