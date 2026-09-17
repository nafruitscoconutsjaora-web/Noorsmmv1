<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class PayoneerGateway extends AbstractGateway
{
    protected string $code = 'payoneer';
    protected string $name = 'Payoneer';
    protected string $category = 'international';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'EUR', 'GBP'];

    public function getCredentialFields(): array
    {
        return [
            'merchant_code' => [
                'label' => 'Merchant Code',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Payoneer Merchant Code',
                'help' => 'Assigned Payoneer Merchant Code.',
            ],
            'api_token' => [
                'label' => 'API Token / Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter API Token',
                'help' => 'Payoneer checkout API Token.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'live' => 'Live (api.live.oscato.com)',
                    'sandbox' => 'Sandbox (api.sandbox.oscato.com)',
                ],
                'help' => 'Payoneer environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'live') === 'sandbox'
            ? 'https://api.sandbox.oscato.com/api'
            : 'https://api.live.oscato.com/api';
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $merchantCode = trim((string)($creds['merchant_code'] ?? ''));
        $token = trim((string)($creds['api_token'] ?? ''));

        if (empty($merchantCode) || empty($token)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Payoneer credentials are not configured.',
            ];
        }

        $orderId = 'PYN_' . $paymentData['order_id'];
        $amount = (float)$paymentData['payable_amount'];
        $currency = strtoupper($paymentData['currency'] ?? 'USD');
        $baseUrl = $this->getBaseUrl($creds);

        $payload = [
            'transactionId' => $orderId,
            'integration' => 'HOSTED',
            'operationType' => 'CHARGE',
            'division' => $merchantCode,
            'payment' => [
                'amount' => $amount,
                'currency' => $currency,
                'reference' => 'Wallet Deposit #' . $paymentData['order_id'],
            ],
            'callback' => [
                'returnUrl' => $paymentData['return_url'],
                'cancelUrl' => $paymentData['cancel_url'],
                'notificationUrl' => $paymentData['notify_url'],
            ],
            'customer' => [
                'number' => (string)($paymentData['user']['id'] ?? '1'),
                'email' => $paymentData['customer_email'] ?? 'customer@example.com',
            ],
        ];

        $headers = [
            'Authorization: Basic ' . base64_encode("{$merchantCode}:{$token}"),
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/lists", $payload, $headers);

        if ($res['success'] && !empty($res['data']['links']['redirect'])) {
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $res['data']['links']['redirect'],
                'gateway_order_id' => (string)($res['data']['identification']['longId'] ?? $orderId),
                'checkout_data' => $res['data'],
                'message' => 'Payoneer hosted session initialized.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Payoneer initialization error.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $interactionCode = (string)$request->input('interactionCode');
        $orderId = (string)$request->input('transactionId', '');

        if ($interactionCode === 'PROCEED_OK') {
            return [
                'success' => true,
                'gateway_order_id' => $orderId,
                'transaction_id' => $orderId,
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
            'error' => 'Payoneer interaction: ' . ($interactionCode ?: 'pending'),
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
