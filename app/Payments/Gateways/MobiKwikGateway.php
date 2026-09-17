<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class MobiKwikGateway extends AbstractGateway
{
    protected string $code = 'mobikwik';
    protected string $name = 'MobiKwik';
    protected string $category = 'india';
    protected string $defaultCurrency = 'INR';
    protected array $supportedCurrencies = ['INR'];

    public function getCredentialFields(): array
    {
        return [
            'mid' => [
                'label' => 'Merchant ID (MID)',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter MobiKwik MID',
                'help' => 'Assigned by MobiKwik.',
            ],
            'secret_key' => [
                'label' => 'Secret Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter MobiKwik Secret Key',
                'help' => 'Key for checksum generation.',
            ],
            'merchant_name' => [
                'label' => 'Merchant Display Name',
                'type' => 'text',
                'required' => false,
                'placeholder' => 'SMM Panel',
                'help' => 'Display name on MobiKwik checkout.',
            ],
        ];
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $mid = trim((string)($creds['mid'] ?? ''));
        $secretKey = trim((string)($creds['secret_key'] ?? ''));

        if (empty($mid) || empty($secretKey)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'MobiKwik credentials are not configured.',
            ];
        }

        $orderId = 'MBK_' . $paymentData['order_id'];
        $amountInPaise = (int)round((float)$paymentData['payable_amount'] * 100);

        // Checksum calculation: cell|email|amount|orderid|redirecturl|mid
        $cell = $paymentData['customer_phone'] ?? '9999999999';
        $email = $paymentData['customer_email'] ?? 'customer@example.com';
        $dataStr = "'{$cell}''{$email}''{$amountInPaise}''{$orderId}''{$paymentData['return_url']}''{$mid}'";
        $checksum = hash_hmac('sha256', $dataStr, $secretKey);

        return [
            'success' => true,
            'action_type' => 'form',
            'form_action' => 'https://wallet.mobikwik.com/walletpg/zaakpay.html',
            'form_method' => 'POST',
            'form_fields' => [
                'mid' => $mid,
                'merchantname' => $creds['merchant_name'] ?? 'SMM Panel',
                'orderid' => $orderId,
                'amount' => (string)$amountInPaise,
                'cell' => $cell,
                'email' => $email,
                'redirecturl' => $paymentData['return_url'],
                'checksum' => $checksum,
            ],
            'gateway_order_id' => $orderId,
            'message' => 'MobiKwik wallet checkout prepared.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $statuscode = (string)$request->input('statuscode', $request->input('status', ''));
        $orderId = (string)$request->input('orderid');
        $amountInPaise = (string)$request->input('amount', '0');
        $refId = (string)$request->input('refid', $orderId);

        if ($statuscode === '0' || $statuscode === 'SUCCESS') {
            return [
                'success' => true,
                'gateway_order_id' => $orderId,
                'transaction_id' => $refId,
                'amount' => (string)((float)$amountInPaise / 100),
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $orderId,
            'transaction_id' => $refId,
            'amount' => (string)((float)$amountInPaise / 100),
            'currency' => 'INR',
            'raw_response' => $request->all(),
            'error' => $request->input('statusdescription') ?: 'MobiKwik payment failed.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
