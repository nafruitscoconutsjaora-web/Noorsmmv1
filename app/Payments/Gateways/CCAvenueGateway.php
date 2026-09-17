<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class CCAvenueGateway extends AbstractGateway
{
    protected string $code = 'ccavenue';
    protected string $name = 'CCAvenue';
    protected string $category = 'india';
    protected string $defaultCurrency = 'INR';
    protected array $supportedCurrencies = ['INR', 'USD', 'EUR', 'GBP', 'AED'];

    public function getCredentialFields(): array
    {
        return [
            'merchant_id' => [
                'label' => 'Merchant ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter CCAvenue Merchant ID',
                'help' => 'Numeric Merchant ID assigned by CCAvenue.',
            ],
            'access_code' => [
                'label' => 'Access Code',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'AV...',
                'help' => 'Alphanumeric Access Code.',
            ],
            'working_key' => [
                'label' => 'Working Key (Encryption Key)',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter 32-character Working Key',
                'help' => 'Encryption Key provided in CCAvenue settings.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (secure.ccavenue.com)',
                    'test' => 'Test (test.ccavenue.com)',
                ],
                'help' => 'Environment mode.',
            ],
        ];
    }

    private function encrypt(string $plainText, string $key): string
    {
        $secretKey = md5($key, true);
        $iv = pack('C*', 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
        $encrypted = openssl_encrypt($plainText, 'AES-128-CBC', $secretKey, OPENSSL_RAW_DATA, $iv);
        return bin2hex($encrypted);
    }

    private function decrypt(string $cipherText, string $key): ?string
    {
        $secretKey = md5($key, true);
        $iv = pack('C*', 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
        $bin = hex2bin($cipherText);
        if ($bin === false) {
            return null;
        }
        $decrypted = openssl_decrypt($bin, 'AES-128-CBC', $secretKey, OPENSSL_RAW_DATA, $iv);
        return $decrypted === false ? null : $decrypted;
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $merchantId = trim((string)($creds['merchant_id'] ?? ''));
        $accessCode = trim((string)($creds['access_code'] ?? ''));
        $workingKey = trim((string)($creds['working_key'] ?? ''));

        if (empty($merchantId) || empty($accessCode) || empty($workingKey)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'CCAvenue credentials are not configured.',
            ];
        }

        $orderId = 'CCA_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $currency = strtoupper($paymentData['currency'] ?? 'INR');

        $params = [
            'merchant_id' => $merchantId,
            'order_id' => $orderId,
            'amount' => $amount,
            'currency' => $currency,
            'redirect_url' => $paymentData['return_url'],
            'cancel_url' => $paymentData['cancel_url'],
            'language' => 'EN',
            'billing_name' => $paymentData['customer_name'] ?? 'Customer',
            'billing_email' => $paymentData['customer_email'] ?? 'customer@example.com',
            'billing_tel' => !empty($paymentData['customer_phone']) ? $paymentData['customer_phone'] : '9999999999',
        ];

        $queryString = http_build_query($params);
        $encRequest = $this->encrypt($queryString, $workingKey);

        $env = $creds['environment'] ?? 'production';
        $actionUrl = $env === 'test'
            ? 'https://test.ccavenue.com/transaction/transaction.do?command=initiateTransaction'
            : 'https://secure.ccavenue.com/transaction/transaction.do?command=initiateTransaction';

        return [
            'success' => true,
            'action_type' => 'form',
            'form_action' => $actionUrl,
            'form_method' => 'POST',
            'form_fields' => [
                'encRequest' => $encRequest,
                'access_code' => $accessCode,
            ],
            'gateway_order_id' => $orderId,
            'message' => 'CCAvenue payment prepared.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $workingKey = trim((string)($creds['working_key'] ?? ''));
        $encResp = (string)$request->input('encResp');

        if (empty($encResp) || empty($workingKey)) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => 'Missing CCAvenue response data.',
            ];
        }

        $decrypted = $this->decrypt($encResp, $workingKey);
        if ($decrypted === null) {
            Logger::error("CCAvenue decryption failure", [], 'payments');
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => 'Could not decrypt CCAvenue response packet.',
            ];
        }

        parse_str($decrypted, $responseParams);
        $orderStatus = (string)($responseParams['order_status'] ?? '');
        $orderId = (string)($responseParams['order_id'] ?? '');
        $trackingId = (string)($responseParams['tracking_id'] ?? $orderId);
        $amount = (string)($responseParams['amount'] ?? '0');
        $currency = (string)($responseParams['currency'] ?? 'INR');

        if ($orderStatus === 'Success') {
            return [
                'success' => true,
                'gateway_order_id' => $orderId,
                'transaction_id' => $trackingId,
                'amount' => $amount,
                'currency' => $currency,
                'raw_response' => $responseParams,
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $orderId,
            'transaction_id' => $trackingId,
            'amount' => $amount,
            'currency' => $currency,
            'raw_response' => $responseParams,
            'error' => 'CCAvenue payment returned with status: ' . $orderStatus,
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
