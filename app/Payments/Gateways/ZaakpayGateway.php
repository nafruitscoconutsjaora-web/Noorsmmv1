<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class ZaakpayGateway extends AbstractGateway
{
    protected string $code = 'zaakpay';
    protected string $name = 'Zaakpay';
    protected string $category = 'india';
    protected string $defaultCurrency = 'INR';
    protected array $supportedCurrencies = ['INR'];

    public function getCredentialFields(): array
    {
        return [
            'merchant_identifier' => [
                'label' => 'Merchant Identifier',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Zaakpay Merchant Identifier',
                'help' => 'Assigned by Zaakpay/MobiKwik.',
            ],
            'secret_key' => [
                'label' => 'Secret Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Zaakpay Secret Key',
                'help' => 'Secret key for checksum calculation.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (api.zaakpay.com)',
                    'staging' => 'Staging (zaakpay.mobikwik.com)',
                ],
                'help' => 'Zaakpay environment.',
            ],
        ];
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $merchantId = trim((string)($creds['merchant_identifier'] ?? ''));
        $secretKey = trim((string)($creds['secret_key'] ?? ''));

        if (empty($merchantId) || empty($secretKey)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Zaakpay credentials are not configured.',
            ];
        }

        $orderId = 'ZP_' . $paymentData['order_id'];
        $amountInPaise = (int)round((float)$paymentData['payable_amount'] * 100);

        $checksumStr = "amount={$amountInPaise}&buyerEmail=" . ($paymentData['customer_email'] ?? '') . "&currency=INR&merchantIdentifier={$merchantId}&orderDetail=Wallet Deposit&orderId={$orderId}&returnUrl=" . $paymentData['return_url'];
        $checksum = hash_hmac('sha256', $checksumStr, $secretKey);

        return [
            'success' => true,
            'action_type' => 'form',
            'form_action' => 'https://api.zaakpay.com/transact',
            'form_method' => 'POST',
            'form_fields' => [
                'merchantIdentifier' => $merchantId,
                'orderId' => $orderId,
                'returnUrl' => $paymentData['return_url'],
                'buyerEmail' => $paymentData['customer_email'] ?? '',
                'amount' => (string)$amountInPaise,
                'currency' => 'INR',
                'orderDetail' => 'Wallet Deposit',
                'checksum' => $checksum,
            ],
            'gateway_order_id' => $orderId,
            'message' => 'Zaakpay checkout initiated.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $responseCode = (string)$request->input('responseCode');
        $orderId = (string)$request->input('orderId');
        $amountInPaise = (string)$request->input('amount', '0');

        if ($responseCode === '100') {
            return [
                'success' => true,
                'gateway_order_id' => $orderId,
                'transaction_id' => $orderId,
                'amount' => (string)((float)$amountInPaise / 100),
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $orderId,
            'transaction_id' => $orderId,
            'amount' => (string)((float)$amountInPaise / 100),
            'currency' => 'INR',
            'raw_response' => $request->all(),
            'error' => $request->input('responseDescription') ?: 'Zaakpay verification failed.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
