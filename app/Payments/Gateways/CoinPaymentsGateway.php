<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class CoinPaymentsGateway extends AbstractGateway
{
    protected string $code = 'coinpayments';
    protected string $name = 'CoinPayments';
    protected string $category = 'crypto';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'BTC', 'ETH', 'USDT', 'LTC', 'TRX'];

    public function getCredentialFields(): array
    {
        return [
            'merchant_id' => [
                'label' => 'Merchant ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter CoinPayments Merchant ID',
                'help' => 'From CoinPayments Account Settings.',
            ],
            'public_key' => [
                'label' => 'Public Key',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Public API Key',
                'help' => 'From CoinPayments API Keys.',
            ],
            'private_key' => [
                'label' => 'Private Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Private API Key',
                'help' => 'CoinPayments Private API Key.',
            ],
            'ipn_secret' => [
                'label' => 'IPN Secret',
                'type' => 'password',
                'required' => false,
                'placeholder' => 'Enter IPN Secret',
                'help' => 'Secret configured in CoinPayments Merchant Settings.',
            ],
        ];
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $pub = trim((string)($creds['public_key'] ?? ''));
        $priv = trim((string)($creds['private_key'] ?? ''));

        if (empty($pub) || empty($priv)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'CoinPayments API credentials are not configured.',
            ];
        }

        $orderId = 'CP_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $currency = strtoupper($paymentData['currency'] ?? 'USD');

        $params = [
            'version' => '1',
            'cmd' => 'create_transaction',
            'amount' => $amount,
            'currency1' => $currency,
            'currency2' => 'USDT.TRC20', // Default crypto or matching
            'buyer_email' => $paymentData['customer_email'] ?? 'buyer@example.com',
            'buyer_name' => $paymentData['customer_name'] ?? 'Buyer',
            'item_name' => 'Wallet Deposit #' . $paymentData['order_id'],
            'item_number' => $orderId,
            'custom' => $paymentData['order_id'],
            'ipn_url' => $paymentData['notify_url'],
            'success_url' => $paymentData['return_url'],
            'cancel_url' => $paymentData['cancel_url'],
            'key' => $pub,
            'format' => 'json',
        ];

        $postData = http_build_query($params);
        $hmac = hash_hmac('sha512', $postData, $priv);

        $headers = [
            'HMAC: ' . $hmac,
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $res = $this->httpRequest('POST', 'https://www.coinpayments.net/api.php', $postData, $headers);

        if ($res['success'] && ($res['data']['error'] ?? '') === 'ok' && !empty($res['data']['result']['checkout_url'])) {
            $checkout = $res['data']['result'];
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => $checkout['checkout_url'],
                'gateway_order_id' => (string)($checkout['txn_id'] ?? $orderId),
                'checkout_data' => $checkout,
                'message' => 'CoinPayments transaction prepared.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'CoinPayments error: ' . ($res['data']['error'] ?? $res['error'] ?? 'Connection error'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $status = (int)$request->input('status', 0);
        $orderId = (string)$request->input('item_number', $request->input('order_id', ''));
        $txnId = (string)$request->input('txn_id', $orderId);

        if ($status >= 100 || $status === 2) {
            return [
                'success' => true,
                'gateway_order_id' => $orderId,
                'transaction_id' => $txnId,
                'amount' => (string)$request->input('amount1', '0'),
                'currency' => (string)$request->input('currency1', 'USD'),
                'raw_response' => $request->all(),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $orderId,
            'transaction_id' => $txnId,
            'amount' => '0',
            'currency' => 'USD',
            'raw_response' => $request->all(),
            'error' => 'Payment pending or unconfirmed: ' . (string)$request->input('status_text', 'pending'),
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        $rawPost = (string)file_get_contents('php://input');
        $creds = $this->getCredentials($gatewayRow);
        $ipnSecret = trim((string)($creds['ipn_secret'] ?? ''));
        $hmacHeader = (string)($_SERVER['HTTP_HMAC'] ?? '');

        if (!empty($ipnSecret) && !empty($hmacHeader)) {
            $expected = hash_hmac('sha512', $rawPost, $ipnSecret);
            if (!hash_equals($expected, $hmacHeader)) {
                return [
                    'success' => false,
                    'gateway_order_id' => '',
                    'transaction_id' => '',
                    'amount' => '0',
                    'currency' => 'USD',
                    'status' => 'failed',
                    'raw_payload' => $rawPost,
                    'error' => 'CoinPayments IPN HMAC signature mismatch.',
                ];
            }
        }

        $post = $_POST;
        $status = (int)($post['status'] ?? 0);
        $orderId = (string)($post['item_number'] ?? $post['custom'] ?? '');
        $txnId = (string)($post['txn_id'] ?? $orderId);

        if ($status >= 100 || $status === 2) {
            return [
                'success' => true,
                'gateway_order_id' => $orderId,
                'transaction_id' => $txnId,
                'amount' => (string)($post['amount1'] ?? '0'),
                'currency' => (string)($post['currency1'] ?? 'USD'),
                'status' => 'success',
                'raw_payload' => $post,
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $orderId,
            'transaction_id' => $txnId,
            'amount' => '0',
            'currency' => 'USD',
            'status' => 'pending',
            'raw_payload' => $post,
            'error' => 'Status: ' . ($post['status_text'] ?? 'pending'),
        ];
    }
}
