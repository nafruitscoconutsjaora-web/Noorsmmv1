<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class CheckoutComGateway extends AbstractGateway
{
    protected string $code = 'checkoutcom';
    protected string $name = 'Checkout.com';
    protected string $category = 'international';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'EUR', 'GBP', 'AED', 'SGD', 'SAR'];

    public function getCredentialFields(): array
    {
        return [
            'public_key' => [
                'label' => 'Public Key',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'pk_...',
                'help' => 'Checkout.com Public Key.',
            ],
            'secret_key' => [
                'label' => 'Secret Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'sk_...',
                'help' => 'Checkout.com Secret Key.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'live' => 'Live (api.checkout.com)',
                    'sandbox' => 'Sandbox (api.sandbox.checkout.com)',
                ],
                'help' => 'Checkout.com API environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'live') === 'sandbox'
            ? 'https://api.sandbox.checkout.com'
            : 'https://api.checkout.com';
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $secretKey = trim((string)($creds['secret_key'] ?? ''));

        if (empty($secretKey)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Checkout.com secret key is not configured.',
            ];
        }

        $orderId = 'CKO_' . $paymentData['order_id'];
        $amount = (int)round((float)$paymentData['payable_amount'] * 100);
        $currency = strtoupper($paymentData['currency'] ?? 'USD');
        $baseUrl = $this->getBaseUrl($creds);

        $payload = [
            'source' => [
                'type' => 'customer',
            ],
            'amount' => $amount,
            'currency' => $currency,
            'reference' => $orderId,
            'description' => 'Wallet Deposit #' . $paymentData['order_id'],
            'success_url' => $paymentData['return_url'] . '?cko_order_id=' . $orderId,
            'failure_url' => $paymentData['cancel_url'] . '?cko_order_id=' . $orderId,
            'customer' => [
                'email' => $paymentData['customer_email'] ?? 'customer@example.com',
                'name' => $paymentData['customer_name'] ?? 'Customer',
            ],
        ];

        // Creates hosted payment / payment session
        $headers = [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/hosted-payments", $payload, $headers);

        if ($res['success'] && !empty($res['data']['_links']['redirect']['href'])) {
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $res['data']['_links']['redirect']['href'],
                'gateway_order_id' => (string)($res['data']['id'] ?? $orderId),
                'checkout_data' => $res['data'],
                'message' => 'Checkout.com session initialized.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Checkout.com initiation error: ' . ($res['data']['error_type'] ?? $res['error'] ?? 'Connection error'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $sessionId = (string)$request->input('cko-session-id', $request->input('cko_order_id', ''));

        if (empty($sessionId)) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'USD',
                'raw_response' => $request->all(),
                'error' => 'Missing Checkout.com session ID.',
            ];
        }

        $creds = $this->getCredentials($gatewayRow);
        $secretKey = trim((string)($creds['secret_key'] ?? ''));
        $baseUrl = $this->getBaseUrl($creds);

        $res = $this->httpRequest('GET', "{$baseUrl}/payments/{$sessionId}", null, [
            'Authorization: Bearer ' . $secretKey,
        ]);

        if ($res['success'] && in_array(strtoupper($res['data']['status'] ?? ''), ['AUTHORIZED', 'CAPTURED', 'PAID'])) {
            $amount = (string)(((float)($res['data']['amount'] ?? 0)) / 100);
            return [
                'success' => true,
                'gateway_order_id' => $sessionId,
                'transaction_id' => (string)($res['data']['id'] ?? $sessionId),
                'amount' => $amount,
                'currency' => (string)($res['data']['currency'] ?? 'USD'),
                'raw_response' => $res['data'],
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $sessionId,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'USD',
            'raw_response' => $res['data'] ?? [],
            'error' => 'Checkout.com payment unconfirmed.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
