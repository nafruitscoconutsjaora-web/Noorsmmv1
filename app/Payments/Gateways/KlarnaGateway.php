<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class KlarnaGateway extends AbstractGateway
{
    protected string $code = 'klarna';
    protected string $name = 'Klarna';
    protected string $category = 'international';
    protected string $defaultCurrency = 'EUR';
    protected array $supportedCurrencies = ['EUR', 'USD', 'GBP', 'SEK', 'NOK', 'DKK'];

    public function getCredentialFields(): array
    {
        return [
            'username' => [
                'label' => 'API Username (UID)',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'K123456_...',
                'help' => 'From Klarna Merchant Portal.',
            ],
            'password' => [
                'label' => 'API Password',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter API Password',
                'help' => 'Klarna API Password.',
            ],
            'region' => [
                'label' => 'Region / Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'eu_live' => 'Europe Live (api.klarna.com)',
                    'eu_test' => 'Europe Playground (api.playground.klarna.com)',
                    'na_live' => 'North America Live (api-na.klarna.com)',
                    'na_test' => 'North America Playground (api-na.playground.klarna.com)',
                ],
                'help' => 'Select Klarna region & environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        $reg = $creds['region'] ?? 'eu_live';
        return match ($reg) {
            'eu_test' => 'https://api.playground.klarna.com',
            'na_live' => 'https://api-na.klarna.com',
            'na_test' => 'https://api-na.playground.klarna.com',
            default => 'https://api.klarna.com',
        };
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $user = trim((string)($creds['username'] ?? ''));
        $pass = trim((string)($creds['password'] ?? ''));

        if (empty($user) || empty($pass)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Klarna credentials are not configured.',
            ];
        }

        $orderId = 'KLR_' . $paymentData['order_id'];
        $currency = strtoupper($paymentData['currency'] ?? 'EUR');
        $totalAmount = (int)round((float)$paymentData['payable_amount'] * 100);
        $baseUrl = $this->getBaseUrl($creds);

        $payload = [
            'purchase_country' => 'DE',
            'purchase_currency' => $currency,
            'locale' => 'en-US',
            'order_amount' => $totalAmount,
            'order_lines' => [
                [
                    'name' => 'Wallet Deposit #' . $paymentData['order_id'],
                    'quantity' => 1,
                    'unit_price' => $totalAmount,
                    'total_amount' => $totalAmount,
                ],
            ],
            'merchant_urls' => [
                'confirmation' => $paymentData['return_url'] . '?order_id=' . $orderId,
            ],
        ];

        $headers = [
            'Authorization: Basic ' . base64_encode("{$user}:{$pass}"),
            'Content-Type: application/json',
        ];

        // Klarna Payments Session
        $res = $this->httpRequest('POST', "{$baseUrl}/payments/v1/sessions", $payload, $headers);

        if ($res['success'] && !empty($res['data']['client_token'])) {
            return [
                'success' => true,
                'action_type' => 'sdk',
                'gateway_order_id' => (string)($res['data']['session_id'] ?? $orderId),
                'checkout_data' => [
                    'client_token' => $res['data']['client_token'],
                    'payment_method_categories' => $res['data']['payment_method_categories'] ?? [],
                ],
                'message' => 'Klarna session initiated.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Klarna session creation error: ' . ($res['data']['error_messages'][0] ?? $res['error'] ?? 'Connection error'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $orderId = (string)$request->input('order_id');
        return [
            'success' => true,
            'gateway_order_id' => $orderId,
            'transaction_id' => (string)$request->input('authorization_token', $orderId),
            'amount' => '0',
            'currency' => 'EUR',
            'raw_response' => $request->all(),
            'error' => null,
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
