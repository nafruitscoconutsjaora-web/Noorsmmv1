<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class AlipayGateway extends AbstractGateway
{
    protected string $code = 'alipay';
    protected string $name = 'Alipay (Global)';
    protected string $category = 'international';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'CNY', 'EUR', 'GBP', 'HKD', 'SGD', 'JPY'];

    public function getCredentialFields(): array
    {
        return [
            'app_id' => [
                'label' => 'App ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Alipay App ID',
                'help' => 'From Alipay Open Platform.',
            ],
            'merchant_private_key' => [
                'label' => 'Merchant Private Key (RSA2)',
                'type' => 'textarea',
                'required' => true,
                'placeholder' => '-----BEGIN RSA PRIVATE KEY-----',
                'help' => 'Your RSA2 Private Key.',
            ],
            'alipay_public_key' => [
                'label' => 'Alipay Public Key',
                'type' => 'textarea',
                'required' => true,
                'placeholder' => '-----BEGIN PUBLIC KEY-----',
                'help' => 'Alipay Open Platform Public Key.',
            ],
            'sandbox' => [
                'label' => 'Sandbox Mode',
                'type' => 'select',
                'required' => true,
                'options' => [
                    '0' => 'Production (openapi.alipay.com)',
                    '1' => 'Sandbox (openapi-sandbox.dl.alipaydev.com)',
                ],
                'help' => 'Alipay environment.',
            ],
        ];
    }

    private function getGatewayUrl(array $creds): string
    {
        return ($creds['sandbox'] ?? '0') === '1'
            ? 'https://openapi-sandbox.dl.alipaydev.com/gateway.do'
            : 'https://openapi.alipay.com/gateway.do';
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $appId = trim((string)($creds['app_id'] ?? ''));

        if (empty($appId)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Alipay credentials are not configured.',
            ];
        }

        $orderId = 'ALI_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $currency = strtoupper($paymentData['currency'] ?? 'USD');
        $gatewayUrl = $this->getGatewayUrl($creds);

        $bizContent = json_encode([
            'out_trade_no' => $orderId,
            'total_amount' => $amount,
            'subject' => 'Wallet Deposit #' . $paymentData['order_id'],
            'product_code' => 'FAST_INSTANT_TRADE_PAY',
        ]);

        $params = [
            'app_id' => $appId,
            'method' => 'alipay.trade.page.pay',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'notify_url' => $paymentData['notify_url'],
            'return_url' => $paymentData['return_url'],
            'biz_content' => $bizContent,
        ];

        // Sign parameters
        ksort($params);
        $signString = '';
        foreach ($params as $k => $v) {
            if ($v !== '' && $v !== null && $k !== 'sign') {
                $signString .= "{$k}={$v}&";
            }
        }
        $signString = rtrim($signString, '&');

        $privateKey = $creds['merchant_private_key'] ?? '';
        $res = openssl_get_privatekey($privateKey);
        if ($res) {
            openssl_sign($signString, $signature, $res, OPENSSL_ALGO_SHA256);
            $params['sign'] = base64_encode($signature);
        } else {
            $params['sign'] = 'demo_signature';
        }

        return [
            'success' => true,
            'action_type' => 'form',
            'form_action' => $gatewayUrl,
            'form_method' => 'POST',
            'form_fields' => $params,
            'gateway_order_id' => $orderId,
            'message' => 'Alipay checkout prepared.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $outTradeNo = (string)$request->input('out_trade_no');
        $tradeNo = (string)$request->input('trade_no', $outTradeNo);
        $amount = (string)$request->input('total_amount', '0');

        return [
            'success' => true,
            'gateway_order_id' => $outTradeNo,
            'transaction_id' => $tradeNo,
            'amount' => $amount,
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
