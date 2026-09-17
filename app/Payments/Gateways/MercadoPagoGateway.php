<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class MercadoPagoGateway extends AbstractGateway
{
    protected string $code = 'mercadopago';
    protected string $name = 'Mercado Pago';
    protected string $category = 'international';
    protected string $defaultCurrency = 'BRL';
    protected array $supportedCurrencies = ['BRL', 'ARS', 'MXN', 'CLP', 'COP', 'PEN', 'UYU'];

    public function getCredentialFields(): array
    {
        return [
            'public_key' => [
                'label' => 'Public Key',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'APP_USR-... or TEST-...',
                'help' => 'From Mercado Pago Developer Dashboard.',
            ],
            'access_token' => [
                'label' => 'Access Token',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'APP_USR-...',
                'help' => 'Production or Test Access Token.',
            ],
            'webhook_secret' => [
                'label' => 'Webhook Signing Secret',
                'type' => 'password',
                'required' => false,
                'placeholder' => 'Enter Webhook Secret',
                'help' => 'For verifying IPN / Webhook notifications.',
            ],
        ];
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $token = trim((string)($creds['access_token'] ?? ''));

        if (empty($token)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Mercado Pago Access Token is not configured.',
            ];
        }

        $orderId = 'MP_' . $paymentData['order_id'];
        $amount = (float)$paymentData['payable_amount'];
        $currency = strtoupper($paymentData['currency'] ?? 'BRL');

        $payload = [
            'items' => [
                [
                    'id' => $orderId,
                    'title' => 'Wallet Deposit #' . $paymentData['order_id'],
                    'currency_id' => $currency,
                    'unit_price' => $amount,
                    'quantity' => 1,
                ],
            ],
            'payer' => [
                'email' => $paymentData['customer_email'] ?? 'test_user@example.com',
                'name' => $paymentData['customer_name'] ?? 'Customer',
            ],
            'back_urls' => [
                'success' => $paymentData['return_url'],
                'failure' => $paymentData['cancel_url'],
                'pending' => $paymentData['return_url'],
            ],
            'auto_return' => 'approved',
            'external_reference' => $orderId,
            'notification_url' => $paymentData['notify_url'],
        ];

        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', 'https://api.mercadopago.com/checkout/preferences', $payload, $headers);

        if ($res['success'] && !empty($res['data']['init_point'])) {
            $isTest = str_starts_with($token, 'TEST-');
            $redirectUrl = $isTest && !empty($res['data']['sandbox_init_point'])
                ? $res['data']['sandbox_init_point']
                : $res['data']['init_point'];

            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $redirectUrl,
                'gateway_order_id' => (string)($res['data']['id'] ?? $orderId),
                'checkout_data' => $res['data'],
                'message' => 'Mercado Pago checkout initialized.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Mercado Pago preference error: ' . ($res['data']['message'] ?? $res['error'] ?? 'Connection failure'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $status = (string)$request->input('collection_status', $request->input('status', ''));
        $paymentId = (string)$request->input('payment_id', $request->input('collection_id', ''));
        $extRef = (string)$request->input('external_reference', '');

        if ($status === 'approved') {
            return [
                'success' => true,
                'gateway_order_id' => $extRef ?: $paymentId,
                'transaction_id' => $paymentId,
                'amount' => '0',
                'currency' => 'BRL',
                'raw_response' => $request->all(),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $extRef ?: $paymentId,
            'transaction_id' => $paymentId,
            'amount' => '0',
            'currency' => 'BRL',
            'raw_response' => $request->all(),
            'error' => 'Mercado Pago status: ' . ($status ?: 'pending'),
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
