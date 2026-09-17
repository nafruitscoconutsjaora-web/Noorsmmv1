<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class PaystackGateway extends AbstractGateway
{
    protected string $code = 'paystack';
    protected string $name = 'Paystack';
    protected string $category = 'international';
    protected string $defaultCurrency = 'NGN';
    protected array $supportedCurrencies = ['NGN', 'GHS', 'ZAR', 'USD', 'KES'];

    public function getCredentialFields(): array
    {
        return [
            'public_key' => [
                'label' => 'Public Key',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'pk_live_...',
                'help' => 'From Paystack Dashboard -> Settings -> API Keys.',
            ],
            'secret_key' => [
                'label' => 'Secret Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'sk_live_...',
                'help' => 'Paystack Secret Key.',
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
                'error' => 'Paystack secret key is not configured.',
            ];
        }

        $orderId = 'PST_' . $paymentData['order_id'];
        $amountInKobo = (int)round((float)$paymentData['payable_amount'] * 100);
        $currency = strtoupper($paymentData['currency'] ?? 'NGN');

        $payload = [
            'amount' => $amountInKobo,
            'currency' => $currency,
            'email' => $paymentData['customer_email'] ?? 'customer@example.com',
            'reference' => $orderId,
            'callback_url' => $paymentData['return_url'],
            'metadata' => [
                'order_id' => $paymentData['order_id'],
                'user_id' => (string)($paymentData['user']['id'] ?? ''),
            ],
        ];

        $headers = [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', 'https://api.paystack.co/transaction/initialize', $payload, $headers);

        if ($res['success'] && !empty($res['data']['status']) && !empty($res['data']['data']['authorization_url'])) {
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $res['data']['data']['authorization_url'],
                'gateway_order_id' => $orderId,
                'checkout_data' => $res['data']['data'],
                'message' => 'Paystack transaction initialized.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Paystack error: ' . ($res['data']['message'] ?? $res['error'] ?? 'Initialization failed'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $reference = (string)$request->input('reference', $request->input('trxref', ''));

        if (empty($reference)) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'NGN',
                'raw_response' => $request->all(),
                'error' => 'Missing Paystack transaction reference.',
            ];
        }

        $creds = $this->getCredentials($gatewayRow);
        $secretKey = trim((string)($creds['secret_key'] ?? ''));

        $res = $this->httpRequest('GET', "https://api.paystack.co/transaction/verify/{$reference}", null, [
            'Authorization: Bearer ' . $secretKey,
        ]);

        if ($res['success'] && !empty($res['data']['status']) && ($res['data']['data']['status'] ?? '') === 'success') {
            $data = $res['data']['data'];
            return [
                'success' => true,
                'gateway_order_id' => $reference,
                'transaction_id' => (string)($data['id'] ?? $reference),
                'amount' => (string)(((float)($data['amount'] ?? 0)) / 100),
                'currency' => (string)($data['currency'] ?? 'NGN'),
                'raw_response' => $res['data'],
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $reference,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'NGN',
            'raw_response' => $res['data'] ?? [],
            'error' => 'Paystack transaction not verified.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        $rawPayload = (string)file_get_contents('php://input');
        $creds = $this->getCredentials($gatewayRow);
        $secretKey = trim((string)($creds['secret_key'] ?? ''));
        $signature = (string)$request->header('X-Paystack-Signature', '');

        if (!empty($secretKey) && !empty($signature)) {
            $expected = hash_hmac('sha512', $rawPayload, $secretKey);
            if (!hash_equals($expected, $signature)) {
                return [
                    'success' => false,
                    'gateway_order_id' => '',
                    'transaction_id' => '',
                    'amount' => '0',
                    'currency' => 'NGN',
                    'status' => 'failed',
                    'raw_payload' => $rawPayload,
                    'error' => 'Paystack webhook signature mismatch.',
                ];
            }
        }

        $event = json_decode($rawPayload, true);
        if (($event['event'] ?? '') === 'charge.success') {
            $data = $event['data'] ?? [];
            return [
                'success' => true,
                'gateway_order_id' => (string)($data['reference'] ?? ''),
                'transaction_id' => (string)($data['id'] ?? ''),
                'amount' => (string)(((float)($data['amount'] ?? 0)) / 100),
                'currency' => (string)($data['currency'] ?? 'NGN'),
                'status' => 'success',
                'raw_payload' => $event,
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => '',
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'NGN',
            'status' => 'pending',
            'raw_payload' => $event,
            'error' => 'Unhandled Paystack event: ' . ($event['event'] ?? 'unknown'),
        ];
    }
}
