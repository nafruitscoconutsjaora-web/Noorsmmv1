<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class EasebuzzGateway extends AbstractGateway
{
    protected string $code = 'easebuzz';
    protected string $name = 'Easebuzz';
    protected string $category = 'india';
    protected string $defaultCurrency = 'INR';
    protected array $supportedCurrencies = ['INR'];

    public function getCredentialFields(): array
    {
        return [
            'merchant_key' => [
                'label' => 'Merchant Key',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Easebuzz Merchant Key',
                'help' => 'Key from Easebuzz dashboard.',
            ],
            'salt' => [
                'label' => 'Merchant Salt',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Easebuzz Salt',
                'help' => 'Salt key for payment hashing.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (pay.easebuzz.in)',
                    'test' => 'Test (testpay.easebuzz.in)',
                ],
                'help' => 'Easebuzz environment mode.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'production') === 'test'
            ? 'https://testpay.easebuzz.in'
            : 'https://pay.easebuzz.in';
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $key = trim((string)($creds['merchant_key'] ?? ''));
        $salt = trim((string)($creds['salt'] ?? ''));

        if (empty($key) || empty($salt)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Easebuzz credentials are not configured.',
            ];
        }

        $txnid = 'EB_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $productinfo = 'Wallet Deposit #' . $paymentData['order_id'];
        $firstname = $paymentData['customer_name'] ?? 'Customer';
        $email = $paymentData['customer_email'] ?? 'customer@example.com';
        $phone = !empty($paymentData['customer_phone']) ? $paymentData['customer_phone'] : '9999999999';

        // Hash: key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10|salt
        $hashSeq = "{$key}|{$txnid}|{$amount}|{$productinfo}|{$firstname}|{$email}|||||||||||{$salt}";
        $hash = strtolower(hash('sha512', $hashSeq));

        $fields = [
            'key' => $key,
            'txnid' => $txnid,
            'amount' => $amount,
            'productinfo' => $productinfo,
            'firstname' => $firstname,
            'phone' => $phone,
            'email' => $email,
            'surl' => $paymentData['return_url'],
            'furl' => $paymentData['cancel_url'],
            'hash' => $hash,
        ];

        $baseUrl = $this->getBaseUrl($creds);
        $res = $this->httpRequest('POST', "{$baseUrl}/payment/initiateLink", http_build_query($fields), [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ]);

        if ($res['success'] && !empty($res['data']['status']) && $res['data']['status'] == 1 && !empty($res['data']['data'])) {
            $accessKey = (string)$res['data']['data'];
            return [
                'success' => true,
                'action_type' => 'redirect',
                'redirect_url' => "{$baseUrl}/pay/{$accessKey}",
                'gateway_order_id' => $txnid,
                'checkout_data' => ['access_key' => $accessKey],
                'message' => 'Easebuzz payment link initiated.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Easebuzz initialization failed: ' . ($res['data']['error_desc'] ?? $res['error'] ?? 'Provider error'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $key = trim((string)($creds['merchant_key'] ?? ''));
        $salt = trim((string)($creds['salt'] ?? ''));

        $status = (string)$request->input('status');
        $txnid = (string)$request->input('txnid');
        $amount = (string)$request->input('amount');
        $productinfo = (string)$request->input('productinfo');
        $firstname = (string)$request->input('firstname');
        $email = (string)$request->input('email');
        $receivedHash = (string)$request->input('hash');
        $easebuzzId = (string)$request->input('easepayid', $txnid);

        if (empty($status) || empty($txnid) || empty($receivedHash)) {
            return [
                'success' => false,
                'gateway_order_id' => $txnid,
                'transaction_id' => $easebuzzId,
                'amount' => '0',
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => 'Missing Easebuzz parameters.',
            ];
        }

        // Return hash: salt|status|||||||||||email|firstname|productinfo|amount|txnid|key
        $checkSeq = "{$salt}|{$status}|||||||||||{$email}|{$firstname}|{$productinfo}|{$amount}|{$txnid}|{$key}";
        $expectedHash = strtolower(hash('sha512', $checkSeq));

        if (!hash_equals($expectedHash, strtolower($receivedHash))) {
            return [
                'success' => false,
                'gateway_order_id' => $txnid,
                'transaction_id' => $easebuzzId,
                'amount' => $amount,
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => 'Cryptographic signature mismatch.',
            ];
        }

        if ($status === 'success') {
            return [
                'success' => true,
                'gateway_order_id' => $txnid,
                'transaction_id' => $easebuzzId,
                'amount' => $amount,
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $txnid,
            'transaction_id' => $easebuzzId,
            'amount' => $amount,
            'currency' => 'INR',
            'raw_response' => $request->all(),
            'error' => 'Easebuzz transaction was not successful.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
