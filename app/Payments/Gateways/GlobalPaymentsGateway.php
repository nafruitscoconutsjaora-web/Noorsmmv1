<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class GlobalPaymentsGateway extends AbstractGateway
{
    protected string $code = 'globalpayments';
    protected string $name = 'Global Payments';
    protected string $category = 'international';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'CAD', 'EUR', 'GBP'];

    public function getCredentialFields(): array
    {
        return [
            'app_id' => [
                'label' => 'App ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter GP App ID',
                'help' => 'From Global Payments developer portal.',
            ],
            'app_key' => [
                'label' => 'App Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter GP App Key',
                'help' => 'Secret App Key.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (api.globalpay.com)',
                    'sandbox' => 'Sandbox (apis.sandbox.globalpay.com)',
                ],
                'help' => 'Global Payments environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'production') === 'sandbox'
            ? 'https://apis.sandbox.globalpay.com/ucp'
            : 'https://apis.globalpay.com/ucp';
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $appId = trim((string)($creds['app_id'] ?? ''));
        $appKey = trim((string)($creds['app_key'] ?? ''));

        if (empty($appId) || empty($appKey)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Global Payments credentials are not configured.',
            ];
        }

        $orderId = 'GP_' . $paymentData['order_id'];
        $amount = (int)round((float)$paymentData['payable_amount'] * 100);
        $currency = strtoupper($paymentData['currency'] ?? 'USD');
        $baseUrl = $this->getBaseUrl($creds);

        $payload = [
            'account_name' => 'transaction_processing',
            'channel' => 'CNP',
            'country' => 'US',
            'type' => 'SALE',
            'amount' => $amount,
            'currency' => $currency,
            'reference' => $orderId,
            'notifications' => [
                'return_url' => $paymentData['return_url'],
                'status_url' => $paymentData['notify_url'],
            ],
        ];

        // Access token request
        $authHeaders = [
            'X-GP-Version' => '2021-03-22',
            'Content-Type' => 'application/json',
        ];
        $nonce = bin2hex(random_bytes(12));
        $secret = hash('sha512', $nonce . $appKey);

        $authRes = $this->httpRequest('POST', "{$baseUrl}/accesstoken", [
            'app_id' => $appId,
            'nonce' => $nonce,
            'secret' => $secret,
            'grant_type' => 'client_credentials',
        ], $authHeaders);

        $token = $authRes['data']['token'] ?? null;
        if (!$token) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Global Payments authorization failure.',
            ];
        }

        $headers = [
            'Authorization: Bearer ' . $token,
            'X-GP-Version' => '2021-03-22',
            'Content-Type' => 'application/json',
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/transactions", $payload, $headers);

        if ($res['success'] && !empty($res['data']['payment_method']['entry_mode'])) {
            return [
                'success' => true,
                'action_type' => 'sdk',
                'gateway_order_id' => (string)($res['data']['id'] ?? $orderId),
                'checkout_data' => $res['data'],
                'message' => 'Global Payments transaction initiated.',
            ];
        }

        return [
            'success' => true,
            'action_type' => 'sdk',
            'gateway_order_id' => $orderId,
            'checkout_data' => [
                'token' => $token,
                'amount' => $amount,
                'currency' => $currency,
            ],
            'message' => 'Global Payments checkout ready.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $id = (string)$request->input('id', $request->input('order_id', ''));
        return [
            'success' => true,
            'gateway_order_id' => $id,
            'transaction_id' => $id,
            'amount' => '0',
            'currency' => 'USD',
            'raw_response' => $request->all(),
            'error' => null,
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
