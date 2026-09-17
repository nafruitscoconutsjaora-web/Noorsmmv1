<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class MollieGateway extends AbstractGateway
{
    protected string $code = 'mollie';
    protected string $name = 'Mollie';
    protected string $category = 'international';
    protected string $defaultCurrency = 'EUR';
    protected array $supportedCurrencies = ['EUR', 'USD', 'GBP', 'CHF', 'PLN'];

    public function getCredentialFields(): array
    {
        return [
            'api_key' => [
                'label' => 'API Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'live_... or test_...',
                'help' => 'Mollie Live or Test API key.',
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
                'error' => 'Mollie API key is not configured.',
            ];
        }

        $orderId = 'MOL_' . $paymentData['order_id'];
        $currency = strtoupper($paymentData['currency'] ?? 'EUR');
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');

        $payload = [
            'amount' => [
                'currency' => $currency,
                'value' => $amount,
            ],
            'description' => 'Wallet Deposit #' . $paymentData['order_id'],
            'redirectUrl' => $paymentData['return_url'] . '?order_id=' . $orderId,
            'webhookUrl' => $paymentData['notify_url'],
            'metadata' => [
                'order_id' => $paymentData['order_id'],
                'user_id' => (string)($paymentData['user']['id'] ?? ''),
            ],
        ];

        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', 'https://api.mollie.com/v2/payments', $payload, $headers);

        if ($res['success'] && !empty($res['data']['_links']['checkout']['href'])) {
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $res['data']['_links']['checkout']['href'],
                'gateway_order_id' => (string)$res['data']['id'],
                'checkout_data' => $res['data'],
                'message' => 'Mollie payment initiated.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Mollie payment error: ' . ($res['data']['detail'] ?? $res['error'] ?? 'Connection failure'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $apiKey = trim((string)($creds['api_key'] ?? ''));
        $paymentId = (string)$request->input('id', $request->input('payment_id', ''));

        if (empty($paymentId)) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'EUR',
                'raw_response' => $request->all(),
                'error' => 'Missing Mollie payment identifier.',
            ];
        }

        $res = $this->httpRequest('GET', "https://api.mollie.com/v2/payments/{$paymentId}", null, [
            'Authorization: Bearer ' . $apiKey,
        ]);

        if ($res['success'] && ($res['data']['status'] ?? '') === 'paid') {
            return [
                'success' => true,
                'gateway_order_id' => $paymentId,
                'transaction_id' => $paymentId,
                'amount' => (string)($res['data']['amount']['value'] ?? '0'),
                'currency' => (string)($res['data']['amount']['currency'] ?? 'EUR'),
                'raw_response' => $res['data'],
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $paymentId,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'EUR',
            'raw_response' => $res['data'] ?? [],
            'error' => 'Mollie payment status: ' . ($res['data']['status'] ?? 'pending'),
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
