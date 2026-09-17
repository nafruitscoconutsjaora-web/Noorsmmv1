<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class WorldpayGateway extends AbstractGateway
{
    protected string $code = 'worldpay';
    protected string $name = 'Worldpay (FIS)';
    protected string $category = 'international';
    protected string $defaultCurrency = 'GBP';
    protected array $supportedCurrencies = ['GBP', 'USD', 'EUR'];

    public function getCredentialFields(): array
    {
        return [
            'service_key' => [
                'label' => 'Service Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'T_SERVICE_KEY_...',
                'help' => 'Worldpay Service Key for API calls.',
            ],
            'client_key' => [
                'label' => 'Client Key',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'T_CLIENT_KEY_...',
                'help' => 'Worldpay Client Key.',
            ],
            'merchant_code' => [
                'label' => 'Merchant Code',
                'type' => 'text',
                'required' => false,
                'placeholder' => 'Enter Worldpay Merchant Code',
                'help' => 'Assigned installation / merchant code.',
            ],
        ];
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $serviceKey = trim((string)($creds['service_key'] ?? ''));

        if (empty($serviceKey)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Worldpay service key is not configured.',
            ];
        }

        $orderId = 'WP_' . $paymentData['order_id'];
        $amount = (int)round((float)$paymentData['payable_amount'] * 100);
        $currency = strtoupper($paymentData['currency'] ?? 'GBP');

        $payload = [
            'token' => 'your-client-token',
            'orderType' => 'ECOM',
            'amount' => $amount,
            'currencyCode' => $currency,
            'name' => $paymentData['customer_name'] ?? 'Customer',
            'orderDescription' => 'Wallet Deposit #' . $paymentData['order_id'],
            'customerOrderCode' => $orderId,
        ];

        $headers = [
            'Authorization: ' . $serviceKey,
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', 'https://api.worldpay.com/v1/orders', $payload, $headers);

        if ($res['success'] && !empty($res['data']['redirectUrl'])) {
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $res['data']['redirectUrl'],
                'gateway_order_id' => (string)($res['data']['orderCode'] ?? $orderId),
                'checkout_data' => $res['data'],
                'message' => 'Worldpay order created.',
            ];
        }

        return [
            'success' => true,
            'action_type' => 'sdk',
            'gateway_order_id' => $orderId,
            'checkout_data' => [
                'client_key' => $creds['client_key'] ?? '',
                'order_code' => $orderId,
                'amount' => $amount,
                'currency' => $currency,
            ],
            'message' => 'Worldpay client checkout prepared.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $orderCode = (string)$request->input('orderCode', $request->input('order_id', ''));
        if (empty($orderCode)) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'GBP',
                'raw_response' => $request->all(),
                'error' => 'Missing Worldpay order code.',
            ];
        }

        $creds = $this->getCredentials($gatewayRow);
        $serviceKey = trim((string)($creds['service_key'] ?? ''));

        $res = $this->httpRequest('GET', "https://api.worldpay.com/v1/orders/{$orderCode}", null, [
            'Authorization: ' . $serviceKey,
        ]);

        if ($res['success'] && in_array(strtoupper($res['data']['paymentStatus'] ?? ''), ['SUCCESS', 'SETTLED', 'AUTHORIZED'])) {
            return [
                'success' => true,
                'gateway_order_id' => $orderCode,
                'transaction_id' => $orderCode,
                'amount' => (string)(((float)($res['data']['amount'] ?? 0)) / 100),
                'currency' => (string)($res['data']['currencyCode'] ?? 'GBP'),
                'raw_response' => $res['data'],
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $orderCode,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'GBP',
            'raw_response' => $res['data'] ?? [],
            'error' => 'Worldpay order unconfirmed.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
