<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class PayGlocalGateway extends AbstractGateway
{
    protected string $code = 'payglocal';
    protected string $name = 'PayGlocal';
    protected string $category = 'india';
    protected string $defaultCurrency = 'INR';
    protected array $supportedCurrencies = ['INR', 'USD', 'EUR', 'GBP', 'AED', 'SGD'];

    public function getCredentialFields(): array
    {
        return [
            'merchant_id' => [
                'label' => 'Merchant ID (MID)',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter PayGlocal MID',
                'help' => 'Assigned by PayGlocal.',
            ],
            'api_key' => [
                'label' => 'API Key / Secret',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter PayGlocal API Key',
                'help' => 'PayGlocal API Key.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (api.payglocal.in)',
                    'uat' => 'UAT (api-uat.payglocal.in)',
                ],
                'help' => 'PayGlocal API environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'production') === 'uat'
            ? 'https://api-uat.payglocal.in/gl/v1'
            : 'https://api.payglocal.in/gl/v1';
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $mid = trim((string)($creds['merchant_id'] ?? ''));
        $apiKey = trim((string)($creds['api_key'] ?? ''));

        if (empty($mid) || empty($apiKey)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'PayGlocal credentials are not configured.',
            ];
        }

        $orderId = 'PGL_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $currency = strtoupper($paymentData['currency'] ?? 'INR');
        $baseUrl = $this->getBaseUrl($creds);

        $payload = [
            'merchantTxnId' => $orderId,
            'paymentAmount' => $amount,
            'paymentCurrency' => $currency,
            'customer' => [
                'email' => $paymentData['customer_email'] ?? 'customer@example.com',
                'mobile' => $paymentData['customer_phone'] ?? '9999999999',
            ],
            'redirectUrl' => $paymentData['return_url'],
            'callbackUrl' => $paymentData['notify_url'],
        ];

        $headers = [
            'x-gl-token: ' . $apiKey,
            'x-gl-merchant-id: ' . $mid,
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/payments/initiate", $payload, $headers);

        if ($res['success'] && !empty($res['data']['data']['redirectUrl'])) {
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $res['data']['data']['redirectUrl'],
                'gateway_order_id' => $orderId,
                'checkout_data' => $res['data']['data'],
                'message' => 'PayGlocal checkout initialized.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'PayGlocal payment error: ' . ($res['data']['message'] ?? $res['error'] ?? 'Connection failure'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $status = (string)$request->input('status');
        $txnId = (string)$request->input('merchantTxnId', $request->input('txnId', ''));

        if ($status === 'SUCCESS' || $status === 'COMPLETED') {
            return [
                'success' => true,
                'gateway_order_id' => $txnId,
                'transaction_id' => (string)$request->input('glTxnId', $txnId),
                'amount' => (string)$request->input('amount', '0'),
                'currency' => (string)$request->input('currency', 'INR'),
                'raw_response' => $request->all(),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $txnId,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'INR',
            'raw_response' => $request->all(),
            'error' => 'PayGlocal verification status: ' . ($status ?: 'unconfirmed'),
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
