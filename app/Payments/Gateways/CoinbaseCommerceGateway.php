<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class CoinbaseCommerceGateway extends AbstractGateway
{
    protected string $code = 'coinbase_commerce';
    protected string $name = 'Coinbase Commerce';
    protected string $category = 'crypto';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'EUR', 'GBP', 'BTC', 'ETH', 'USDC', 'USDT', 'LTC', 'DOGE'];

    public function getCredentialFields(): array
    {
        return [
            'api_key' => [
                'label' => 'Coinbase Commerce API Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter API Key',
                'help' => 'From Coinbase Commerce Settings -> Security.',
            ],
            'webhook_secret' => [
                'label' => 'Shared Webhook Secret',
                'type' => 'password',
                'required' => false,
                'placeholder' => 'Enter Webhook Secret',
                'help' => 'Secret used to verify X-CC-Webhook-Signature.',
            ],
        ];
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $apiKey = trim((string)($creds['api_key'] ?? ''));

        if (empty($apiKey)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Coinbase Commerce API Key is not configured.',
            ];
        }

        $orderId = 'CBC_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $currency = strtoupper($paymentData['currency'] ?? 'USD');

        $payload = [
            'name' => config('app.name', 'SMM Panel') . ' Wallet Deposit',
            'description' => 'Wallet Deposit #' . $paymentData['order_id'],
            'pricing_type' => 'fixed_price',
            'local_price' => [
                'amount' => $amount,
                'currency' => $currency,
            ],
            'metadata' => [
                'order_id' => $paymentData['order_id'],
                'user_id' => (string)($paymentData['user']['id'] ?? ''),
            ],
            'redirect_url' => $paymentData['return_url'],
            'cancel_url' => $paymentData['cancel_url'],
        ];

        $headers = [
            'X-CC-Api-Key: ' . $apiKey,
            'X-CC-Version: 2018-03-22',
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', 'https://api.commerce.coinbase.com/charges', $payload, $headers);

        if ($res['success'] && !empty($res['data']['data']['hosted_url'])) {
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $res['data']['data']['hosted_url'],
                'gateway_order_id' => (string)($res['data']['data']['code'] ?? $orderId),
                'checkout_data' => $res['data']['data'],
                'message' => 'Coinbase Commerce charge created.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Coinbase Commerce error: ' . ($res['data']['error']['message'] ?? $res['error'] ?? 'Connection error'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $code = (string)$request->input('charge_code', $request->input('order_id', ''));
        return [
            'success' => true,
            'gateway_order_id' => $code,
            'transaction_id' => $code,
            'amount' => '0',
            'currency' => 'USD',
            'raw_response' => $request->all(),
            'error' => null,
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        $rawPayload = (string)file_get_contents('php://input');
        $creds = $this->getCredentials($gatewayRow);
        $secret = trim((string)($creds['webhook_secret'] ?? ''));
        $sigHeader = (string)$request->header('X-CC-Webhook-Signature', '');

        if (!empty($secret) && !empty($sigHeader)) {
            $expected = hash_hmac('sha256', $rawPayload, $secret);
            if (!hash_equals($expected, $sigHeader)) {
                return [
                    'success' => false,
                    'gateway_order_id' => '',
                    'transaction_id' => '',
                    'amount' => '0',
                    'currency' => 'USD',
                    'status' => 'failed',
                    'raw_payload' => $rawPayload,
                    'error' => 'Coinbase Commerce signature verification failed.',
                ];
            }
        }

        $event = json_decode($rawPayload, true);
        $eventType = $event['event']['type'] ?? '';
        $data = $event['event']['data'] ?? [];

        if (in_array($eventType, ['charge:confirmed', 'charge:resolved'])) {
            return [
                'success' => true,
                'gateway_order_id' => (string)($data['metadata']['order_id'] ?? $data['code'] ?? ''),
                'transaction_id' => (string)($data['id'] ?? $data['code'] ?? ''),
                'amount' => (string)($data['pricing']['local']['amount'] ?? '0'),
                'currency' => (string)($data['pricing']['local']['currency'] ?? 'USD'),
                'status' => 'success',
                'raw_payload' => $event,
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => (string)($data['code'] ?? ''),
            'transaction_id' => (string)($data['id'] ?? ''),
            'amount' => '0',
            'currency' => 'USD',
            'status' => 'pending',
            'raw_payload' => $event,
            'error' => 'Coinbase event: ' . $eventType,
        ];
    }
}
