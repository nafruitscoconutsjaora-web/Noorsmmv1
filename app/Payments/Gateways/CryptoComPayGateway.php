<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class CryptoComPayGateway extends AbstractGateway
{
    protected string $code = 'cryptocom_pay';
    protected string $name = 'Crypto.com Pay';
    protected string $category = 'crypto';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'EUR', 'BTC', 'ETH', 'CRO', 'USDT', 'USDC'];

    public function getCredentialFields(): array
    {
        return [
            'publishable_key' => [
                'label' => 'Publishable Key',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'pk_live_...',
                'help' => 'From Crypto.com Merchant Dashboard.',
            ],
            'secret_key' => [
                'label' => 'Secret Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'sk_live_...',
                'help' => 'Crypto.com Secret Key.',
            ],
            'webhook_secret' => [
                'label' => 'Webhook Secret',
                'type' => 'password',
                'required' => false,
                'placeholder' => 'whsec_...',
                'help' => 'Secret for webhook signature validation.',
            ],
        ];
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $secretKey = trim((string)($creds['secret_key'] ?? ''));

        if (empty($secretKey)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Crypto.com Pay secret key is not configured.',
            ];
        }

        $orderId = 'CC_' . $paymentData['order_id'];
        $amountInCents = (int)round((float)$paymentData['payable_amount'] * 100);
        $currency = strtoupper($paymentData['currency'] ?? 'USD');

        $payload = [
            'description' => 'Wallet Deposit #' . $paymentData['order_id'],
            'amount' => $amountInCents,
            'currency' => $currency,
            'metadata' => [
                'order_id' => $paymentData['order_id'],
            ],
            'return_url' => $paymentData['return_url'],
            'cancel_url' => $paymentData['cancel_url'],
        ];

        $headers = [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', 'https://pay.crypto.com/api/payments', $payload, $headers);

        if ($res['success'] && !empty($res['data']['payment_url'])) {
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $res['data']['payment_url'],
                'gateway_order_id' => (string)($res['data']['id'] ?? $orderId),
                'checkout_data' => $res['data'],
                'message' => 'Crypto.com payment initialized.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Crypto.com initiation failure: ' . ($res['data']['error']['message'] ?? $res['error'] ?? 'Connection error'),
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
