<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class CashfreeGateway extends AbstractGateway
{
    protected string $code = 'cashfree';
    protected string $name = 'Cashfree Payments';
    protected string $category = 'india';
    protected string $defaultCurrency = 'INR';
    protected array $supportedCurrencies = ['INR', 'USD'];

    public function getCredentialFields(): array
    {
        return [
            'app_id' => [
                'label' => 'App ID / Client ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'CF_APP_xxxxxxxx',
                'help' => 'Cashfree Merchant App ID.',
            ],
            'secret_key' => [
                'label' => 'Secret Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Cashfree Secret Key',
                'help' => 'Cashfree API Secret Key.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (api.cashfree.com)',
                    'sandbox' => 'Sandbox (sandbox.cashfree.com)',
                ],
                'help' => 'Select production or sandbox environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'production') === 'sandbox'
            ? 'https://sandbox.cashfree.com/pg'
            : 'https://api.cashfree.com/pg';
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $appId = trim((string)($creds['app_id'] ?? ''));
        $secretKey = trim((string)($creds['secret_key'] ?? ''));

        if (empty($appId) || empty($secretKey)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Cashfree credentials are not configured.',
            ];
        }

        $baseUrl = $this->getBaseUrl($creds);
        $orderId = 'cf_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $currency = strtoupper($paymentData['currency'] ?? 'INR');

        $payload = [
            'order_id' => $orderId,
            'order_amount' => (float)$amount,
            'order_currency' => $currency,
            'customer_details' => [
                'customer_id' => 'cust_' . ($paymentData['user']['id'] ?? 'guest'),
                'customer_name' => $paymentData['customer_name'] ?? 'SMM Customer',
                'customer_email' => $paymentData['customer_email'] ?? 'customer@example.com',
                'customer_phone' => !empty($paymentData['customer_phone']) ? $paymentData['customer_phone'] : '9999999999',
            ],
            'order_meta' => [
                'return_url' => $paymentData['return_url'] . '?cf_order_id=' . $orderId,
                'notify_url' => $paymentData['notify_url'],
            ],
            'order_note' => 'Wallet Deposit #' . $paymentData['order_id'],
        ];

        $headers = [
            'x-client-id' => $appId,
            'x-client-secret' => $secretKey,
            'x-api-version' => '2023-08-01',
            'Content-Type' => 'application/json',
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/orders", $payload, $headers);

        if (!$res['success'] || empty($res['data']['payment_session_id'])) {
            $err = $res['data']['message'] ?? $res['error'] ?? 'Cashfree order generation failed.';
            Logger::error("Cashfree order error: {$err}", [], 'payments');
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Unable to create Cashfree payment: ' . $err,
            ];
        }

        $sessionId = (string)$res['data']['payment_session_id'];
        $redirectUrl = "{$baseUrl}/orders/{$orderId}";

        return [
            'success' => true,
            'action_type' => 'redirect',
            'redirect_url' => $redirectUrl,
            'gateway_order_id' => $orderId,
            'checkout_data' => [
                'payment_session_id' => $sessionId,
                'order_id' => $orderId,
            ],
            'message' => 'Cashfree order created.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $appId = trim((string)($creds['app_id'] ?? ''));
        $secretKey = trim((string)($creds['secret_key'] ?? ''));
        $cfOrderId = (string)$request->input('cf_order_id', $request->input('order_id', ''));

        if (empty($cfOrderId) || empty($appId) || empty($secretKey)) {
            return [
                'success' => false,
                'gateway_order_id' => $cfOrderId,
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => 'Missing Cashfree verification parameters.',
            ];
        }

        $baseUrl = $this->getBaseUrl($creds);
        $headers = [
            'x-client-id' => $appId,
            'x-client-secret' => $secretKey,
            'x-api-version' => '2023-08-01',
        ];

        $res = $this->httpRequest('GET', "{$baseUrl}/orders/{$cfOrderId}", null, $headers);

        if ($res['success'] && isset($res['data']['order_status']) && $res['data']['order_status'] === 'PAID') {
            $paidAmount = (string)($res['data']['order_amount'] ?? '0');
            $currency = (string)($res['data']['order_currency'] ?? 'INR');
            return [
                'success' => true,
                'gateway_order_id' => $cfOrderId,
                'transaction_id' => (string)($res['data']['cf_order_id'] ?? $cfOrderId),
                'amount' => $paidAmount,
                'currency' => $currency,
                'raw_response' => $res['data'],
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $cfOrderId,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'INR',
            'raw_response' => $res['data'] ?? [],
            'error' => 'Payment status is not verified or pending.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $secretKey = trim((string)($creds['secret_key'] ?? ''));
        $rawBody = (string)file_get_contents('php://input');

        $data = json_decode($rawBody, true);
        if (!$data || !isset($data['type'])) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'status' => 'failed',
                'raw_payload' => $rawBody,
                'error' => 'Invalid Cashfree webhook format.',
            ];
        }

        if ($data['type'] === 'PAYMENT_SUCCESS_WEBHOOK') {
            $order = $data['data']['order'] ?? [];
            $payment = $data['data']['payment'] ?? [];

            return [
                'success' => true,
                'gateway_order_id' => (string)($order['order_id'] ?? ''),
                'transaction_id' => (string)($payment['cf_payment_id'] ?? ''),
                'amount' => (string)($payment['payment_amount'] ?? 0),
                'currency' => (string)($payment['payment_currency'] ?? 'INR'),
                'status' => 'success',
                'raw_payload' => $data,
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => '',
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'INR',
            'status' => 'pending',
            'raw_payload' => $data,
            'error' => 'Ignored event: ' . ($data['type'] ?? 'unknown'),
        ];
    }
}
