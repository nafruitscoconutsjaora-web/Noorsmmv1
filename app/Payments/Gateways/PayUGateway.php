<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class PayUGateway extends AbstractGateway
{
    protected string $code = 'payu';
    protected string $name = 'PayU';
    protected string $category = 'india';
    protected string $defaultCurrency = 'INR';
    protected array $supportedCurrencies = ['INR', 'USD'];

    public function getCredentialFields(): array
    {
        return [
            'merchant_key' => [
                'label' => 'Merchant Key',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter PayU Merchant Key',
                'help' => 'Key provided by PayU onboarding dashboard.',
            ],
            'merchant_salt' => [
                'label' => 'Merchant Salt',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter PayU Merchant Salt',
                'help' => 'Secret Salt for SHA-512 payment hash generation.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (secure.payu.in)',
                    'test' => 'Test (test.payu.in)',
                ],
                'help' => 'PayU environment mode.',
            ],
        ];
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $key = trim((string)($creds['merchant_key'] ?? ''));
        $salt = trim((string)($creds['merchant_salt'] ?? ''));

        if (empty($key) || empty($salt)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'PayU credentials are not configured.',
            ];
        }

        $txnId = 'PAYU_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $productInfo = 'Wallet Funds #' . $paymentData['order_id'];
        $firstName = $paymentData['customer_name'] ?? 'Customer';
        $email = $paymentData['customer_email'] ?? 'customer@example.com';
        $phone = !empty($paymentData['customer_phone']) ? $paymentData['customer_phone'] : '9999999999';

        // Hash Sequence: key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||salt
        $hashString = "{$key}|{$txnId}|{$amount}|{$productInfo}|{$firstName}|{$email}|||||||||||{$salt}";
        $hash = strtolower(hash('sha512', $hashString));

        $env = $creds['environment'] ?? 'production';
        $actionUrl = $env === 'test' ? 'https://test.payu.in/_payment' : 'https://secure.payu.in/_payment';

        return [
            'success' => true,
            'action_type' => 'form',
            'form_action' => $actionUrl,
            'form_method' => 'POST',
            'form_fields' => [
                'key' => $key,
                'txnid' => $txnId,
                'amount' => $amount,
                'productinfo' => $productInfo,
                'firstname' => $firstName,
                'email' => $email,
                'phone' => $phone,
                'surl' => $paymentData['return_url'],
                'furl' => $paymentData['cancel_url'],
                'hash' => $hash,
                'service_provider' => 'payu_paisa',
            ],
            'gateway_order_id' => $txnId,
            'message' => 'PayU transaction prepared.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $key = trim((string)($creds['merchant_key'] ?? ''));
        $salt = trim((string)($creds['merchant_salt'] ?? ''));

        $status = (string)$request->input('status');
        $txnId = (string)$request->input('txnid');
        $amount = (string)$request->input('amount');
        $productInfo = (string)$request->input('productinfo');
        $firstName = (string)$request->input('firstname');
        $email = (string)$request->input('email');
        $receivedHash = (string)$request->input('hash');
        $payuMoneyId = (string)$request->input('payuMoneyId', $request->input('mihpayid', $txnId));

        if (empty($status) || empty($txnId) || empty($receivedHash) || empty($salt)) {
            return [
                'success' => false,
                'gateway_order_id' => $txnId,
                'transaction_id' => $payuMoneyId,
                'amount' => '0',
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => 'Missing PayU response parameters.',
            ];
        }

        // Return Hash Sequence: salt|status||||||udf5|udf4|udf3|udf2|udf1|email|firstname|productinfo|amount|txnid|key
        $checkString = "{$salt}|{$status}|||||||||||{$email}|{$firstName}|{$productInfo}|{$amount}|{$txnId}|{$key}";
        $expectedHash = strtolower(hash('sha512', $checkString));

        if (!hash_equals($expectedHash, strtolower($receivedHash))) {
            Logger::error("PayU hash verification failed for txn {$txnId}", [], 'payments');
            return [
                'success' => false,
                'gateway_order_id' => $txnId,
                'transaction_id' => $payuMoneyId,
                'amount' => $amount,
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => 'Cryptographic return signature mismatch.',
            ];
        }

        if ($status === 'success') {
            return [
                'success' => true,
                'gateway_order_id' => $txnId,
                'transaction_id' => $payuMoneyId,
                'amount' => $amount,
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $txnId,
            'transaction_id' => $payuMoneyId,
            'amount' => $amount,
            'currency' => 'INR',
            'raw_response' => $request->all(),
            'error' => 'Payment status was: ' . $status,
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
