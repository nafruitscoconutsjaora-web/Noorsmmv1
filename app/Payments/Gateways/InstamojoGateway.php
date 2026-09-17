<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class InstamojoGateway extends AbstractGateway
{
    protected string $code = 'instamojo';
    protected string $name = 'Instamojo';
    protected string $category = 'india';
    protected string $defaultCurrency = 'INR';
    protected array $supportedCurrencies = ['INR'];

    public function getCredentialFields(): array
    {
        return [
            'client_id' => [
                'label' => 'Client ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Instamojo Client ID',
                'help' => 'From Instamojo developer dashboard.',
            ],
            'client_secret' => [
                'label' => 'Client Secret',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Instamojo Client Secret',
                'help' => 'Secret for OAuth authorization.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (api.instamojo.com)',
                    'test' => 'Test (test.instamojo.com)',
                ],
                'help' => 'Instamojo environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'production') === 'test'
            ? 'https://test.instamojo.com'
            : 'https://api.instamojo.com';
    }

    private function getAccessToken(array $creds): ?string
    {
        $clientId = trim((string)($creds['client_id'] ?? ''));
        $clientSecret = trim((string)($creds['client_secret'] ?? ''));
        $baseUrl = $this->getBaseUrl($creds);

        $res = $this->httpRequest('POST', "{$baseUrl}/oauth2/token/", [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ], ['Content-Type: application/x-www-form-urlencoded']);

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
                'error' => 'Instamojo authorization failed. Check client ID and secret.',
            ];
        }

        $baseUrl = $this->getBaseUrl($creds);
        $payload = [
            'purpose' => 'Wallet Deposit #' . $paymentData['order_id'],
            'amount' => number_format((float)$paymentData['payable_amount'], 2, '.', ''),
            'buyer_name' => $paymentData['customer_name'] ?? 'Customer',
            'email' => $paymentData['customer_email'] ?? 'customer@example.com',
            'phone' => !empty($paymentData['customer_phone']) ? $paymentData['customer_phone'] : '9999999999',
            'redirect_url' => $paymentData['return_url'],
            'webhook' => $paymentData['notify_url'],
            'send_email' => false,
            'send_sms' => false,
            'allow_repeated_payments' => false,
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/v2/payment_requests/", $payload, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ]);

        if ($res['success'] && !empty($res['data']['longurl'])) {
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $res['data']['longurl'],
                'gateway_order_id' => (string)($res['data']['id'] ?? $paymentData['order_id']),
                'checkout_data' => $res['data'],
                'message' => 'Instamojo request created.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Unable to create Instamojo request.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $paymentId = (string)$request->input('payment_id');
        $paymentRequestId = (string)$request->input('payment_request_id');

        if (empty($paymentId) || empty($paymentRequestId)) {
            return [
                'success' => false,
                'gateway_order_id' => $paymentRequestId,
                'transaction_id' => $paymentId,
                'amount' => '0',
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => 'Missing Instamojo verification identifiers.',
            ];
        }

        $token = $this->getAccessToken($creds);
        if ($token) {
            $baseUrl = $this->getBaseUrl($creds);
            $res = $this->httpRequest('GET', "{$baseUrl}/v2/payments/{$paymentId}/", null, [
                'Authorization: Bearer ' . $token,
            ]);

            if ($res['success'] && ($res['data']['status'] ?? '') === true) {
                return [
                    'success' => true,
                    'gateway_order_id' => $paymentRequestId,
                    'transaction_id' => $paymentId,
                    'amount' => (string)($res['data']['amount'] ?? '0'),
                    'currency' => 'INR',
                    'raw_response' => $res['data'],
                    'error' => null,
                ];
            }
        }

        return [
            'success' => false,
            'gateway_order_id' => $paymentRequestId,
            'transaction_id' => $paymentId,
            'amount' => '0',
            'currency' => 'INR',
            'raw_response' => $request->all(),
            'error' => 'Instamojo verification failed.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
