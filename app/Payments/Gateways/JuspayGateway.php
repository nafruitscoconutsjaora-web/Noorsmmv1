<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class JuspayGateway extends AbstractGateway
{
    protected string $code = 'juspay';
    protected string $name = 'Juspay / HyperCheckout';
    protected string $category = 'india';
    protected string $defaultCurrency = 'INR';
    protected array $supportedCurrencies = ['INR', 'USD'];

    public function getCredentialFields(): array
    {
        return [
            'api_key' => [
                'label' => 'API Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Juspay API Key',
                'help' => 'From Juspay Merchant Dashboard.',
            ],
            'merchant_id' => [
                'label' => 'Merchant ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Merchant ID',
                'help' => 'Juspay Merchant ID.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (api.juspay.in)',
                    'sandbox' => 'Sandbox (sandbox.juspay.in)',
                ],
                'help' => 'Juspay environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'production') === 'sandbox'
            ? 'https://sandbox.juspay.in'
            : 'https://api.juspay.in';
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $apiKey = trim((string)($creds['api_key'] ?? ''));
        $mid = trim((string)($creds['merchant_id'] ?? ''));

        if (empty($apiKey) || empty($mid)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Juspay credentials are not configured.',
            ];
        }

        $orderId = 'JP_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $baseUrl = $this->getBaseUrl($creds);

        $payload = [
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => strtoupper($paymentData['currency'] ?? 'INR'),
            'customer_id' => 'cust_' . ($paymentData['user']['id'] ?? '1'),
            'customer_email' => $paymentData['customer_email'] ?? 'customer@example.com',
            'customer_phone' => $paymentData['customer_phone'] ?? '9999999999',
            'return_url' => $paymentData['return_url'],
        ];

        $headers = [
            'Authorization: Basic ' . base64_encode($apiKey . ':'),
            'x-merchantid: ' . $mid,
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/session", $payload, $headers);

        if ($res['success'] && !empty($res['data']['payment_links']['web'])) {
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $res['data']['payment_links']['web'],
                'gateway_order_id' => $orderId,
                'checkout_data' => $res['data'],
                'message' => 'Juspay session created.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Juspay session generation failed: ' . ($res['data']['error_message'] ?? $res['error'] ?? 'Provider error'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $orderId = (string)$request->input('order_id', $request->input('orderId', ''));
        $status = (string)$request->input('status');

        if ($status === 'CHARGED' || $status === 'SUCCESS') {
            return [
                'success' => true,
                'gateway_order_id' => $orderId,
                'transaction_id' => (string)$request->input('txn_id', $orderId),
                'amount' => (string)$request->input('amount', '0'),
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $orderId,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'INR',
            'raw_response' => $request->all(),
            'error' => 'Juspay payment status: ' . ($status ?: 'unknown'),
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
