<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class AirwallexGateway extends AbstractGateway
{
    protected string $code = 'airwallex';
    protected string $name = 'Airwallex';
    protected string $category = 'international';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'EUR', 'GBP', 'AUD', 'HKD', 'SGD', 'CAD'];

    public function getCredentialFields(): array
    {
        return [
            'client_id' => [
                'label' => 'Client ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Airwallex Client ID',
                'help' => 'From Airwallex Developer settings.',
            ],
            'api_key' => [
                'label' => 'API Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Airwallex API Key',
                'help' => 'Secret API Key.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (api.airwallex.com)',
                    'demo' => 'Demo (api-demo.airwallex.com)',
                ],
                'help' => 'Airwallex environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'production') === 'demo'
            ? 'https://api-demo.airwallex.com/api/v1'
            : 'https://api.airwallex.com/api/v1';
    }

    private function getAuthToken(array $creds): ?string
    {
        $clientId = trim((string)($creds['client_id'] ?? ''));
        $apiKey = trim((string)($creds['api_key'] ?? ''));
        $baseUrl = $this->getBaseUrl($creds);

        $headers = [
            'x-client-id: ' . $clientId,
            'x-api-key: ' . $apiKey,
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/authentication/login", '{}', $headers);
        return $res['success'] && !empty($res['data']['token']) ? (string)$res['data']['token'] : null;
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $token = $this->getAuthToken($creds);

        if (!$token) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Airwallex authentication failed.',
            ];
        }

        $orderId = 'AWX_' . $paymentData['order_id'];
        $amount = (float)$paymentData['payable_amount'];
        $currency = strtoupper($paymentData['currency'] ?? 'USD');
        $baseUrl = $this->getBaseUrl($creds);

        $payload = [
            'request_id' => 'REQ_' . bin2hex(random_bytes(8)),
            'amount' => $amount,
            'currency' => $currency,
            'merchant_order_id' => $orderId,
            'return_url' => $paymentData['return_url'],
            'customer' => [
                'email' => $paymentData['customer_email'] ?? 'customer@example.com',
            ],
        ];

        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/pa/payment_intents/create", $payload, $headers);

        if ($res['success'] && !empty($res['data']['id'])) {
            return [
                'success' => true,
                'action_type' => 'sdk',
                'gateway_order_id' => (string)$res['data']['id'],
                'checkout_data' => [
                    'intent_id' => $res['data']['id'],
                    'client_secret' => $res['data']['client_secret'] ?? '',
                    'currency' => $currency,
                    'amount' => $amount,
                ],
                'message' => 'Airwallex Payment Intent created.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Failed to create Airwallex payment intent: ' . ($res['data']['message'] ?? $res['error'] ?? 'Error'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $intentId = (string)$request->input('payment_intent_id', $request->input('intent_id', ''));
        if (empty($intentId)) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'USD',
                'raw_response' => $request->all(),
                'error' => 'Missing Airwallex payment intent ID.',
            ];
        }

        $creds = $this->getCredentials($gatewayRow);
        $token = $this->getAuthToken($creds);
        $baseUrl = $this->getBaseUrl($creds);

        $res = $this->httpRequest('GET', "{$baseUrl}/pa/payment_intents/{$intentId}", null, [
            'Authorization: Bearer ' . $token,
        ]);

        if ($res['success'] && in_array(strtoupper($res['data']['status'] ?? ''), ['SUCCEEDED', 'PAID'])) {
            return [
                'success' => true,
                'gateway_order_id' => $intentId,
                'transaction_id' => $intentId,
                'amount' => (string)($res['data']['amount'] ?? '0'),
                'currency' => (string)($res['data']['currency'] ?? 'USD'),
                'raw_response' => $res['data'],
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $intentId,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'USD',
            'raw_response' => $res['data'] ?? [],
            'error' => 'Payment status: ' . ($res['data']['status'] ?? 'pending'),
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
