<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class AdyenGateway extends AbstractGateway
{
    protected string $code = 'adyen';
    protected string $name = 'Adyen';
    protected string $category = 'international';
    protected string $defaultCurrency = 'EUR';
    protected array $supportedCurrencies = ['EUR', 'USD', 'GBP', 'AUD', 'SGD', 'CAD'];

    public function getCredentialFields(): array
    {
        return [
            'api_key' => [
                'label' => 'API Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'AQE...',
                'help' => 'Adyen Web Service API key.',
            ],
            'merchant_account' => [
                'label' => 'Merchant Account',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Adyen Merchant Account',
                'help' => 'Merchant Account name in Adyen CA.',
            ],
            'client_key' => [
                'label' => 'Client Key',
                'type' => 'text',
                'required' => false,
                'placeholder' => 'test_...',
                'help' => 'Client Key for web drop-in components.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'test' => 'Test (checkout-test.adyen.com)',
                    'live' => 'Live (checkout-live.adyen.com)',
                ],
                'help' => 'Adyen environment.',
            ],
            'live_url_prefix' => [
                'label' => 'Live URL Prefix',
                'type' => 'text',
                'required' => false,
                'placeholder' => 'e.g. 1797a79a70acf309-MyCompany',
                'help' => 'Required only for live environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        $env = $creds['environment'] ?? 'test';
        if ($env === 'live') {
            $prefix = $creds['live_url_prefix'] ?? '';
            return "https://{$prefix}-checkout-live.adyenpayments.com/checkout/v71";
        }
        return 'https://checkout-test.adyen.com/v71';
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $apiKey = trim((string)($creds['api_key'] ?? ''));
        $merchantAccount = trim((string)($creds['merchant_account'] ?? ''));

        if (empty($apiKey) || empty($merchantAccount)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Adyen credentials are not configured.',
            ];
        }

        $orderId = 'ADY_' . $paymentData['order_id'];
        $currency = strtoupper($paymentData['currency'] ?? 'EUR');
        $multiplier = in_array(strtolower($currency), ['jpy', 'krw']) ? 1 : 100;
        $amountInSubunit = (int)round((float)$paymentData['payable_amount'] * $multiplier);
        $baseUrl = $this->getBaseUrl($creds);

        $payload = [
            'merchantAccount' => $merchantAccount,
            'amount' => [
                'value' => $amountInSubunit,
                'currency' => $currency,
            ],
            'returnUrl' => $paymentData['return_url'] . '?order_id=' . $orderId,
            'reference' => $orderId,
            'countryCode' => 'US',
            'shopperEmail' => $paymentData['customer_email'] ?? 'shopper@example.com',
        ];

        $headers = [
            'x-api-key: ' . $apiKey,
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/sessions", $payload, $headers);

        if ($res['success'] && !empty($res['data']['url'])) {
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $res['data']['url'],
                'gateway_order_id' => $orderId,
                'checkout_data' => $res['data'],
                'message' => 'Adyen payment session initialized.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Adyen checkout session creation failed: ' . ($res['data']['message'] ?? $res['error'] ?? 'Connection error'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $sessionId = (string)$request->input('sessionId');
        $orderId = (string)$request->input('order_id');
        $resultCode = (string)$request->input('resultCode', 'Authorised');

        if ($resultCode === 'Authorised') {
            return [
                'success' => true,
                'gateway_order_id' => $orderId,
                'transaction_id' => $sessionId ?: $orderId,
                'amount' => '0',
                'currency' => 'EUR',
                'raw_response' => $request->all(),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $orderId,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'EUR',
            'raw_response' => $request->all(),
            'error' => 'Adyen payment result: ' . $resultCode,
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
