<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class PhonePeGateway extends AbstractGateway
{
    protected string $code = 'phonepe';
    protected string $name = 'PhonePe Payment Gateway';
    protected string $category = 'india';
    protected string $defaultCurrency = 'INR';
    protected array $supportedCurrencies = ['INR'];

    public function getCredentialFields(): array
    {
        return [
            'merchant_id' => [
                'label' => 'Merchant ID (MID)',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Mxxxxxxxxxxxx',
                'help' => 'PhonePe Merchant ID provided by PhonePe onboarding.',
            ],
            'salt_key' => [
                'label' => 'Salt Key / API Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Salt Key',
                'help' => 'Secret Salt Key for payload checksum calculation.',
            ],
            'salt_index' => [
                'label' => 'Salt Index',
                'type' => 'text',
                'required' => true,
                'placeholder' => '1',
                'help' => 'Salt Key index (normally 1).',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (api.phonepe.com)',
                    'uat' => 'UAT Sandbox (api-preprod.phonepe.com)',
                ],
                'help' => 'Select production or UAT testing environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'production') === 'uat'
            ? 'https://api-preprod.phonepe.com/apis/pg-sandbox'
            : 'https://api.phonepe.com/apis/hermes';
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $merchantId = trim((string)($creds['merchant_id'] ?? ''));
        $saltKey = trim((string)($creds['salt_key'] ?? ''));
        $saltIndex = trim((string)($creds['salt_index'] ?? '1'));

        if (empty($merchantId) || empty($saltKey)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'PhonePe credentials are not configured.',
            ];
        }

        $merchantTxnId = 'TXN_' . $paymentData['order_id'];
        $amountInPaise = (int)round((float)$paymentData['payable_amount'] * 100);
        $userId = 'CUST_' . ($paymentData['user']['id'] ?? '1');

        $payload = [
            'merchantId' => $merchantId,
            'merchantTransactionId' => $merchantTxnId,
            'merchantUserId' => $userId,
            'amount' => $amountInPaise,
            'redirectUrl' => $paymentData['return_url'] . '?txn_id=' . $merchantTxnId,
            'redirectMode' => 'REDIRECT',
            'callbackUrl' => $paymentData['notify_url'],
            'mobileNumber' => !empty($paymentData['customer_phone']) ? $paymentData['customer_phone'] : '9999999999',
            'paymentInstrument' => [
                'type' => 'PAY_PAGE',
            ],
        ];

        $encodedPayload = base64_encode(json_encode($payload));
        $endpoint = '/pg/v1/pay';
        $checksum = hash('sha256', $encodedPayload . $endpoint . $saltKey) . '###' . $saltIndex;

        $baseUrl = $this->getBaseUrl($creds);
        $headers = [
            'Content-Type' => 'application/json',
            'X-VERIFY' => $checksum,
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}{$endpoint}", ['request' => $encodedPayload], $headers);

        if (!$res['success'] || empty($res['data']['success']) || empty($res['data']['data']['instrumentResponse']['redirectInfo']['url'])) {
            $err = $res['data']['message'] ?? $res['error'] ?? 'Failed to initialize PhonePe payment.';
            Logger::error("PhonePe pay error: {$err}", [], 'payments');
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Unable to initiate PhonePe transaction: ' . $err,
            ];
        }

        $redirectUrl = (string)$res['data']['data']['instrumentResponse']['redirectInfo']['url'];

        return [
            'success' => true,
            'action_type' => 'redirect',
            'redirect_url' => $redirectUrl,
            'gateway_order_id' => $merchantTxnId,
            'checkout_data' => [
                'transaction_id' => $merchantTxnId,
                'redirect_url' => $redirectUrl,
            ],
            'message' => 'PhonePe checkout created.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $merchantId = trim((string)($creds['merchant_id'] ?? ''));
        $saltKey = trim((string)($creds['salt_key'] ?? ''));
        $saltIndex = trim((string)($creds['salt_index'] ?? '1'));
        $merchantTxnId = (string)$request->input('txn_id', $request->input('transactionId', ''));

        if (empty($merchantId) || empty($saltKey) || empty($merchantTxnId)) {
            return [
                'success' => false,
                'gateway_order_id' => $merchantTxnId,
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => 'Missing PhonePe verification parameters.',
            ];
        }

        $endpoint = "/pg/v1/status/{$merchantId}/{$merchantTxnId}";
        $checksum = hash('sha256', $endpoint . $saltKey) . '###' . $saltIndex;

        $baseUrl = $this->getBaseUrl($creds);
        $headers = [
            'Content-Type' => 'application/json',
            'X-VERIFY' => $checksum,
            'X-MERCHANT-ID' => $merchantId,
        ];

        $res = $this->httpRequest('GET', "{$baseUrl}{$endpoint}", null, $headers);

        if ($res['success'] && !empty($res['data']['success']) && ($res['data']['code'] ?? '') === 'PAYMENT_SUCCESS') {
            $data = $res['data']['data'] ?? [];
            $amountInPaise = (float)($data['amount'] ?? 0);
            return [
                'success' => true,
                'gateway_order_id' => $merchantTxnId,
                'transaction_id' => (string)($data['transactionId'] ?? $merchantTxnId),
                'amount' => (string)($amountInPaise / 100),
                'currency' => 'INR',
                'raw_response' => $res['data'],
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $merchantTxnId,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'INR',
            'raw_response' => $res['data'] ?? [],
            'error' => $res['data']['message'] ?? 'Payment verification failed.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $saltKey = trim((string)($creds['salt_key'] ?? ''));
        $saltIndex = trim((string)($creds['salt_index'] ?? '1'));

        $rawBody = (string)file_get_contents('php://input');
        $verifyHeader = (string)$request->header('X-VERIFY', '');

        $json = json_decode($rawBody, true);
        if (!$json || empty($json['response'])) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'status' => 'failed',
                'raw_payload' => $rawBody,
                'error' => 'Malformed PhonePe webhook payload.',
            ];
        }

        $base64Response = (string)$json['response'];
        $expectedVerify = hash('sha256', $base64Response . $saltKey) . '###' . $saltIndex;

        if (!empty($verifyHeader) && !hash_equals($expectedVerify, $verifyHeader)) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'status' => 'failed',
                'raw_payload' => $rawBody,
                'error' => 'Invalid PhonePe webhook checksum.',
            ];
        }

        $decoded = json_decode(base64_decode($base64Response), true);
        if (!$decoded) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'status' => 'failed',
                'raw_payload' => $rawBody,
                'error' => 'Could not decode PhonePe response.',
            ];
        }

        if (!empty($decoded['success']) && ($decoded['code'] ?? '') === 'PAYMENT_SUCCESS') {
            $data = $decoded['data'] ?? [];
            return [
                'success' => true,
                'gateway_order_id' => (string)($data['merchantTransactionId'] ?? ''),
                'transaction_id' => (string)($data['transactionId'] ?? ''),
                'amount' => (string)(($data['amount'] ?? 0) / 100),
                'currency' => 'INR',
                'status' => 'success',
                'raw_payload' => $decoded,
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => (string)($decoded['data']['merchantTransactionId'] ?? ''),
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'INR',
            'status' => 'failed',
            'raw_payload' => $decoded,
            'error' => $decoded['message'] ?? 'Payment was not successful.',
        ];
    }
}
