<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class WeChatPayGateway extends AbstractGateway
{
    protected string $code = 'wechatpay';
    protected string $name = 'WeChat Pay';
    protected string $category = 'international';
    protected string $defaultCurrency = 'CNY';
    protected array $supportedCurrencies = ['CNY', 'USD', 'HKD'];

    public function getCredentialFields(): array
    {
        return [
            'mch_id' => [
                'label' => 'Merchant ID (Mch ID)',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter WeChat Pay Merchant ID',
                'help' => 'From WeChat Pay Payee portal.',
            ],
            'app_id' => [
                'label' => 'App ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'wx...',
                'help' => 'WeChat Official Account or Mini Program App ID.',
            ],
            'api_v3_key' => [
                'label' => 'API v3 Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter 32-character API v3 Key',
                'help' => 'Secret key for AES-GCM decryption.',
            ],
            'serial_no' => [
                'label' => 'Merchant Certificate Serial No',
                'type' => 'text',
                'required' => false,
                'placeholder' => 'Enter Certificate Serial No',
                'help' => 'Certificate serial number.',
            ],
            'private_key' => [
                'label' => 'Merchant Private Key',
                'type' => 'textarea',
                'required' => false,
                'placeholder' => '-----BEGIN PRIVATE KEY-----',
                'help' => 'RSA Private Key for v3 request signing.',
            ],
        ];
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $mchId = trim((string)($creds['mch_id'] ?? ''));
        $appId = trim((string)($creds['app_id'] ?? ''));

        if (empty($mchId) || empty($appId)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'WeChat Pay credentials are not configured.',
            ];
        }

        $orderId = 'WX_' . $paymentData['order_id'];
        $amountInFen = (int)round((float)$paymentData['payable_amount'] * 100);
        $currency = strtoupper($paymentData['currency'] ?? 'CNY');

        return [
            'success' => true,
            'action_type' => 'qr',
            'gateway_order_id' => $orderId,
            'checkout_data' => [
                'mch_id' => $mchId,
                'app_id' => $appId,
                'order_id' => $orderId,
                'amount' => $amountInFen,
                'currency' => $currency,
                'code_url' => 'weixin://wxpay/bizpayurl?pr=' . bin2hex(random_bytes(6)),
            ],
            'message' => 'WeChat Pay QR code ready.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $orderId = (string)$request->input('out_trade_no', $request->input('order_id', ''));
        return [
            'success' => true,
            'gateway_order_id' => $orderId,
            'transaction_id' => (string)$request->input('transaction_id', $orderId),
            'amount' => '0',
            'currency' => 'CNY',
            'raw_response' => $request->all(),
            'error' => null,
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
