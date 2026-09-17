<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class PluralGateway extends AbstractGateway
{
    protected string $code = 'plural';
    protected string $name = 'Pine Labs / Plural';
    protected string $category = 'india';
    protected string $defaultCurrency = 'INR';
    protected array $supportedCurrencies = ['INR'];

    public function getCredentialFields(): array
    {
        return [
            'merchant_id' => [
                'label' => 'Merchant ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Pine Labs Merchant ID',
                'help' => 'Assigned by Pine Labs / Plural.',
            ],
            'access_code' => [
                'label' => 'Access Code',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Access Code',
                'help' => 'Plural access code.',
            ],
            'secret_key' => [
                'label' => 'Secret Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Secret Key',
                'help' => 'Secret key for HMAC authentication.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (api.pluralonline.com)',
                    'uat' => 'UAT Sandbox (pluralqa.pinepg.in)',
                ],
                'help' => 'Plural environment mode.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'production') === 'uat'
            ? 'https://pluralqa.pinepg.in/api/v1'
            : 'https://api.pluralonline.com/api/v1';
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $mid = trim((string)($creds['merchant_id'] ?? ''));
        $secretKey = trim((string)($creds['secret_key'] ?? ''));

        if (empty($mid) || empty($secretKey)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Plural credentials are not configured.',
            ];
        }

        $orderId = 'PL_' . $paymentData['order_id'];
        $amountInPaise = (int)round((float)$paymentData['payable_amount'] * 100);

        $payload = [
            'merchant_id' => $mid,
            'merchant_order_reference' => $orderId,
            'order_amount' => [
                'value' => $amountInPaise,
                'currency' => 'INR',
            ],
            'customer_details' => [
                'email_id' => $paymentData['customer_email'] ?? 'customer@example.com',
                'mobile_number' => !empty($paymentData['customer_phone']) ? $paymentData['customer_phone'] : '9999999999',
            ],
            'callback_url' => $paymentData['return_url'],
        ];

        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', $jsonPayload, $secretKey);

        $baseUrl = $this->getBaseUrl($creds);
        $headers = [
            'Content-Type' => 'application/json',
            'x-verify' => $signature,
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/order/create", $jsonPayload, $headers);

        if ($res['success'] && !empty($res['data']['payment_redirect_url'])) {
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $res['data']['payment_redirect_url'],
                'gateway_order_id' => $orderId,
                'checkout_data' => $res['data'],
                'message' => 'Pine Labs payment created.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Plural checkout could not be created: ' . ($res['data']['error_message'] ?? $res['error'] ?? 'Connection error'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $secretKey = trim((string)($creds['secret_key'] ?? ''));
        $orderRef = (string)$request->input('order_reference', $request->input('merchant_order_reference', ''));

        if (empty($orderRef)) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => 'Missing Plural order reference.',
            ];
        }

        $paymentStatus = (string)$request->input('payment_status');
        if (strtoupper($paymentStatus) === 'SUCCESS' || strtoupper($paymentStatus) === 'CHARGED') {
            return [
                'success' => true,
                'gateway_order_id' => $orderRef,
                'transaction_id' => (string)$request->input('plural_order_id', $orderRef),
                'amount' => (string)$request->input('amount', '0'),
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $orderRef,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'INR',
            'raw_response' => $request->all(),
            'error' => 'Plural transaction was not successful.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
