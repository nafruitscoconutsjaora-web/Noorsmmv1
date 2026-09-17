<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class BinancePayGateway extends AbstractGateway
{
    protected string $code = 'binancepay';
    protected string $name = 'Binance Pay';
    protected string $category = 'crypto';
    protected string $defaultCurrency = 'USDT';
    protected array $supportedCurrencies = ['USDT', 'BUSD', 'BTC', 'ETH', 'BNB', 'USD', 'EUR'];

    public function getCredentialFields(): array
    {
        return [
            'api_key' => [
                'label' => 'Binance Pay API Key',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Binance Pay API Key',
                'help' => 'From Binance Merchant Portal.',
            ],
            'secret_key' => [
                'label' => 'Binance Pay Secret Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Binance Pay Secret Key',
                'help' => 'Secret Key for HMAC-SHA512 header signature.',
            ],
        ];
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $apiKey = trim((string)($creds['api_key'] ?? ''));
        $secretKey = trim((string)($creds['secret_key'] ?? ''));

        if (empty($apiKey) || empty($secretKey)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Binance Pay credentials are not configured.',
            ];
        }

        $orderId = 'BNB_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $currency = strtoupper($paymentData['currency'] ?? 'USDT');
        if ($currency === 'USD') {
            $currency = 'USDT';
        }

        $payload = [
            'env' => [
                'terminalType' => 'WEB',
            ],
            'merchantTradeNo' => $orderId,
            'orderAmount' => (float)$amount,
            'currency' => $currency,
            'goods' => [
                'goodsType' => '02',
                'goodsCategory' => '6000',
                'referenceGoodsId' => 'WALLET',
                'goodsName' => 'Wallet Deposit #' . $paymentData['order_id'],
                'goodsDetail' => 'Account balance top-up',
            ],
            'returnUrl' => $paymentData['return_url'],
            'cancelUrl' => $paymentData['cancel_url'],
        ];

        $jsonPayload = json_encode($payload);
        $nonce = bin2hex(random_bytes(16));
        $timestamp = (string)round(microtime(true) * 1000);

        // Binance Pay signature: timestamp + "\n" + nonce + "\n" + body + "\n"
        $signString = "{$timestamp}\n{$nonce}\n{$jsonPayload}\n";
        $signature = strtoupper(hash_hmac('sha512', $signString, $secretKey));

        $headers = [
            'Content-Type: application/json',
            'BinancePay-Timestamp: ' . $timestamp,
            'BinancePay-Nonce: ' . $nonce,
            'BinancePay-Certificate-SN: ' . $apiKey,
            'BinancePay-Signature: ' . $signature,
        ];

        $res = $this->httpRequest('POST', 'https://bpay.binanceapi.com/binancepay/openapi/v2/order', $jsonPayload, $headers);

        if ($res['success'] && ($res['data']['status'] ?? '') === 'SUCCESS' && !empty($res['data']['data']['universalUrl'])) {
            $checkoutUrl = $res['data']['data']['universalUrl'];
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $checkoutUrl,
                'gateway_order_id' => (string)($res['data']['data']['prepayId'] ?? $orderId),
                'checkout_data' => $res['data']['data'],
                'message' => 'Binance Pay order created.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Binance Pay error: ' . ($res['data']['errorMessage'] ?? $res['error'] ?? 'Connection failure'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $orderId = (string)$request->input('merchantTradeNo', $request->input('order_id', ''));
        return [
            'success' => true,
            'gateway_order_id' => $orderId,
            'transaction_id' => $orderId,
            'amount' => '0',
            'currency' => 'USDT',
            'raw_response' => $request->all(),
            'error' => null,
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        $rawPayload = (string)file_get_contents('php://input');
        $event = json_decode($rawPayload, true);

        if (($event['bizStatus'] ?? '') === 'PAY_SUCCESS') {
            $data = json_decode($event['data'] ?? '{}', true);
            return [
                'success' => true,
                'gateway_order_id' => (string)($data['merchantTradeNo'] ?? ''),
                'transaction_id' => (string)($data['transactionId'] ?? ''),
                'amount' => (string)($data['totalFee'] ?? '0'),
                'currency' => (string)($data['currency'] ?? 'USDT'),
                'status' => 'success',
                'raw_payload' => $event,
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => '',
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'USDT',
            'status' => 'pending',
            'raw_payload' => $event ?? [],
            'error' => 'Binance Pay status: ' . ($event['bizStatus'] ?? 'unknown'),
        ];
    }
}
