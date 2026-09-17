<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class AmazonPayGateway extends AbstractGateway
{
    protected string $code = 'amazonpay';
    protected string $name = 'Amazon Pay';
    protected string $category = 'international';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'EUR', 'GBP', 'JPY'];

    public function getCredentialFields(): array
    {
        return [
            'merchant_id' => [
                'label' => 'Merchant / Seller ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Amazon Seller ID',
                'help' => 'From Amazon Seller Central.',
            ],
            'public_key_id' => [
                'label' => 'Public Key ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Public Key ID',
                'help' => 'Amazon Pay API Public Key ID.',
            ],
            'private_key' => [
                'label' => 'Private Key (PEM)',
                'type' => 'textarea',
                'required' => true,
                'placeholder' => '-----BEGIN RSA PRIVATE KEY-----',
                'help' => 'Your RSA Private Key.',
            ],
            'region' => [
                'label' => 'Region',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'na' => 'North America (pay-api.amazon.com)',
                    'eu' => 'Europe (pay-api.amazon.eu)',
                    'jp' => 'Japan (pay-api.amazon.jp)',
                ],
                'help' => 'Amazon Pay operational region.',
            ],
            'sandbox' => [
                'label' => 'Sandbox Mode',
                'type' => 'select',
                'required' => true,
                'options' => [
                    '0' => 'Live / Production',
                    '1' => 'Sandbox Mode',
                ],
                'help' => 'Enable sandbox testing mode.',
            ],
        ];
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $merchantId = trim((string)($creds['merchant_id'] ?? ''));

        if (empty($merchantId)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Amazon Pay credentials are not configured.',
            ];
        }

        $orderId = 'AMZ_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $currency = strtoupper($paymentData['currency'] ?? 'USD');

        return [
            'success' => true,
            'action_type' => 'sdk',
            'gateway_order_id' => $orderId,
            'checkout_data' => [
                'merchant_id' => $merchantId,
                'public_key_id' => $creds['public_key_id'] ?? '',
                'amount' => $amount,
                'currency' => $currency,
                'order_id' => $orderId,
            ],
            'message' => 'Amazon Pay checkout prepared.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $checkoutSessionId = (string)$request->input('amazonCheckoutSessionId', $request->input('order_id', ''));
        return [
            'success' => true,
            'gateway_order_id' => $checkoutSessionId,
            'transaction_id' => $checkoutSessionId,
            'amount' => '0',
            'currency' => 'USD',
            'raw_response' => $request->all(),
            'error' => null,
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
