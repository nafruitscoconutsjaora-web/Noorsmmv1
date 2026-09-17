<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class DLocalGateway extends AbstractGateway
{
    protected string $code = 'dlocal';
    protected string $name = 'dLocal';
    protected string $category = 'international';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'BRL', 'MXN', 'ARS', 'CLP', 'COP'];

    public function getCredentialFields(): array
    {
        return [
            'x_login' => [
                'label' => 'X-Login',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter dLocal X-Login',
                'help' => 'Assigned by dLocal dashboard.',
            ],
            'x_trans_key' => [
                'label' => 'X-Trans-Key',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter dLocal X-Trans-Key',
                'help' => 'Transaction Key.',
            ],
            'secret_key' => [
                'label' => 'Secret Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter dLocal Secret Key',
                'help' => 'Secret Key for HMAC signatures.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (api.dlocal.com)',
                    'sandbox' => 'Sandbox (sandbox.dlocal.com)',
                ],
                'help' => 'dLocal environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'production') === 'sandbox'
            ? 'https://sandbox.dlocal.com'
            : 'https://api.dlocal.com';
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $xLogin = trim((string)($creds['x_login'] ?? ''));
        $xTransKey = trim((string)($creds['x_trans_key'] ?? ''));
        $secretKey = trim((string)($creds['secret_key'] ?? ''));

        if (empty($xLogin) || empty($secretKey)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'dLocal credentials are not configured.',
            ];
        }

        $orderId = 'DLC_' . $paymentData['order_id'];
        $amount = (float)$paymentData['payable_amount'];
        $currency = strtoupper($paymentData['currency'] ?? 'USD');
        $date = gmdate('Y-m-d\TH:i:s\Z');
        $baseUrl = $this->getBaseUrl($creds);

        $payload = [
            'amount' => $amount,
            'currency' => $currency,
            'country' => 'BR',
            'order_id' => $orderId,
            'description' => 'Wallet Deposit #' . $paymentData['order_id'],
            'notification_url' => $paymentData['notify_url'],
            'callback_url' => $paymentData['return_url'],
            'payer' => [
                'name' => $paymentData['customer_name'] ?? 'Customer',
                'email' => $paymentData['customer_email'] ?? 'customer@example.com',
            ],
        ];

        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', "{$xLogin}{$date}{$jsonPayload}", $secretKey);

        $headers = [
            'X-Date: ' . $date,
            'X-Login: ' . $xLogin,
            'X-Trans-Key: ' . $xTransKey,
            'Authorization: V2-HMAC-SHA256, Signature: ' . $signature,
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/payments", $jsonPayload, $headers);

        if ($res['success'] && !empty($res['data']['redirect_url'])) {
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $res['data']['redirect_url'],
                'gateway_order_id' => (string)($res['data']['id'] ?? $orderId),
                'checkout_data' => $res['data'],
                'message' => 'dLocal checkout initialized.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'dLocal payment initiation failed: ' . ($res['data']['message'] ?? $res['error'] ?? 'Network error'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $orderId = (string)$request->input('order_id');
        $status = (string)$request->input('status');

        if (in_array(strtoupper($status), ['PAID', 'SUCCESS', 'SETTLED'])) {
            return [
                'success' => true,
                'gateway_order_id' => $orderId,
                'transaction_id' => (string)$request->input('payment_id', $orderId),
                'amount' => (string)$request->input('amount', '0'),
                'currency' => (string)$request->input('currency', 'USD'),
                'raw_response' => $request->all(),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $orderId,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'USD',
            'raw_response' => $request->all(),
            'error' => 'dLocal payment unconfirmed.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
