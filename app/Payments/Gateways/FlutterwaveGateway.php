<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class FlutterwaveGateway extends AbstractGateway
{
    protected string $code = 'flutterwave';
    protected string $name = 'Flutterwave';
    protected string $category = 'international';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'NGN', 'KES', 'GHS', 'ZAR', 'EUR', 'GBP'];

    public function getCredentialFields(): array
    {
        return [
            'public_key' => [
                'label' => 'Public Key',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'FLWPUBK-...',
                'help' => 'From Flutterwave Dashboard -> Settings -> API Keys.',
            ],
            'secret_key' => [
                'label' => 'Secret Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'FLWSECK-...',
                'help' => 'Flutterwave Secret Key.',
            ],
            'encryption_key' => [
                'label' => 'Encryption Key',
                'type' => 'password',
                'required' => false,
                'placeholder' => 'FLWSECK_...',
                'help' => 'Secret hash for webhook verification.',
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
                'error' => 'Flutterwave secret key is not configured.',
            ];
        }

        $orderId = 'FLW_' . $paymentData['order_id'];
        $amount = (float)$paymentData['payable_amount'];
        $currency = strtoupper($paymentData['currency'] ?? 'USD');

        $payload = [
            'tx_ref' => $orderId,
            'amount' => $amount,
            'currency' => $currency,
            'redirect_url' => $paymentData['return_url'],
            'customer' => [
                'email' => $paymentData['customer_email'] ?? 'customer@example.com',
                'name' => $paymentData['customer_name'] ?? 'Customer',
                'phonenumber' => $paymentData['customer_phone'] ?? '',
            ],
            'customizations' => [
                'title' => config('app.name', 'SMM Panel') . ' Deposit',
                'description' => 'Wallet Deposit #' . $paymentData['order_id'],
            ],
        ];

        $headers = [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', 'https://api.flutterwave.com/v3/payments', $payload, $headers);

        if ($res['success'] && !empty($res['data']['status']) && $res['data']['status'] === 'success' && !empty($res['data']['data']['link'])) {
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $res['data']['data']['link'],
                'gateway_order_id' => $orderId,
                'checkout_data' => $res['data']['data'],
                'message' => 'Flutterwave payment initiated.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Flutterwave initiation error: ' . ($res['data']['message'] ?? $res['error'] ?? 'Connection error'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $transactionId = (string)$request->input('transaction_id');
        $txRef = (string)$request->input('tx_ref');

        if (empty($transactionId)) {
            return [
                'success' => false,
                'gateway_order_id' => $txRef,
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'USD',
                'raw_response' => $request->all(),
                'error' => 'Missing Flutterwave transaction ID.',
            ];
        }

        $creds = $this->getCredentials($gatewayRow);
        $secretKey = trim((string)($creds['secret_key'] ?? ''));

        $res = $this->httpRequest('GET', "https://api.flutterwave.com/v3/transactions/{$transactionId}/verify", null, [
            'Authorization: Bearer ' . $secretKey,
        ]);

        if ($res['success'] && !empty($res['data']['status']) && $res['data']['status'] === 'success' && ($res['data']['data']['status'] ?? '') === 'successful') {
            $data = $res['data']['data'];
            return [
                'success' => true,
                'gateway_order_id' => (string)($data['tx_ref'] ?? $txRef),
                'transaction_id' => (string)($data['id'] ?? $transactionId),
                'amount' => (string)($data['amount'] ?? '0'),
                'currency' => (string)($data['currency'] ?? 'USD'),
                'raw_response' => $res['data'],
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $txRef,
            'transaction_id' => $transactionId,
            'amount' => '0',
            'currency' => 'USD',
            'raw_response' => $res['data'] ?? [],
            'error' => 'Flutterwave verification unsuccessful: ' . ($res['data']['message'] ?? 'unconfirmed'),
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        $rawPayload = (string)file_get_contents('php://input');
        $creds = $this->getCredentials($gatewayRow);
        $secretHash = trim((string)($creds['encryption_key'] ?? ''));
        $headerHash = (string)$request->header('verif-hash', '');

        if (!empty($secretHash) && !hash_equals($secretHash, $headerHash)) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'USD',
                'status' => 'failed',
                'raw_payload' => $rawPayload,
                'error' => 'Invalid Flutterwave webhook secret hash.',
            ];
        }

        $event = json_decode($rawPayload, true);
        if (($event['event'] ?? '') === 'charge.completed' && ($event['data']['status'] ?? '') === 'successful') {
            $data = $event['data'];
            return [
                'success' => true,
                'gateway_order_id' => (string)($data['tx_ref'] ?? ''),
                'transaction_id' => (string)($data['id'] ?? ''),
                'amount' => (string)($data['amount'] ?? '0'),
                'currency' => (string)($data['currency'] ?? 'USD'),
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
            'currency' => 'USD',
            'status' => 'pending',
            'raw_payload' => $event,
            'error' => 'Ignored event: ' . ($event['event'] ?? 'unknown'),
        ];
    }
}
