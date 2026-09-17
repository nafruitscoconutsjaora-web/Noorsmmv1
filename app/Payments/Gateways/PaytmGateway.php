<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class PaytmGateway extends AbstractGateway
{
    protected string $code = 'paytm';
    protected string $name = 'Paytm Payment Gateway';
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
                'placeholder' => 'Enter Paytm MID',
                'help' => 'Paytm Merchant ID from Dashboard.',
            ],
            'merchant_key' => [
                'label' => 'Merchant Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Paytm Merchant Key',
                'help' => 'Paytm Secret Merchant Key for HMAC.',
            ],
            'website' => [
                'label' => 'Website Name',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'DEFAULT',
                'help' => 'DEFAULT for production or WEBSTAGING for testing.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (securegw.paytm.in)',
                    'staging' => 'Staging (securegw-stage.paytm.in)',
                ],
                'help' => 'Paytm environment mode.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'production') === 'staging'
            ? 'https://securegw-stage.paytm.in'
            : 'https://securegw.paytm.in';
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $mid = trim((string)($creds['mid'] ?? ''));
        $key = trim((string)($creds['merchant_key'] ?? ''));
        $website = trim((string)($creds['website'] ?? 'DEFAULT'));

        if (empty($mid) || empty($key)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Paytm credentials are not configured.',
            ];
        }

        $orderId = 'PTM_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $custId = 'CUST_' . ($paymentData['user']['id'] ?? '1');

        $body = [
            'requestType' => 'Payment',
            'mid' => $mid,
            'websiteName' => $website,
            'orderId' => $orderId,
            'callbackUrl' => $paymentData['return_url'],
            'txnAmount' => [
                'value' => $amount,
                'currency' => 'INR',
            ],
            'userInfo' => [
                'custId' => $custId,
            ],
        ];

        $jsonBody = json_encode($body);
        $signature = hash_hmac('sha256', $jsonBody, $key);

        $payload = [
            'body' => $body,
            'head' => [
                'signature' => $signature,
            ],
        ];

        $baseUrl = $this->getBaseUrl($creds);
        $url = "{$baseUrl}/theia/api/v1/initiateTransaction?mid={$mid}&orderId={$orderId}";

        $res = $this->httpRequest('POST', $url, $payload, ['Content-Type' => 'application/json']);

        if (!$res['success'] || empty($res['data']['body']['txnToken'])) {
            $err = $res['data']['body']['resultInfo']['resultMsg'] ?? $res['error'] ?? 'Paytm initiate failed.';
            Logger::error("Paytm initiate error: {$err}", [], 'payments');
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Unable to initialize Paytm payment: ' . $err,
            ];
        }

        $txnToken = (string)$res['data']['body']['txnToken'];
        $actionUrl = "{$baseUrl}/theia/api/v1/showPaymentPage?mid={$mid}&orderId={$orderId}";

        return [
            'success' => true,
            'action_type' => 'form',
            'form_action' => $actionUrl,
            'form_method' => 'POST',
            'form_fields' => [
                'mid' => $mid,
                'orderId' => $orderId,
                'txnToken' => $txnToken,
            ],
            'gateway_order_id' => $orderId,
            'message' => 'Paytm transaction prepared.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $mid = trim((string)($creds['mid'] ?? ''));
        $key = trim((string)($creds['merchant_key'] ?? ''));
        $orderId = (string)$request->input('ORDERID', $request->input('orderId', ''));

        if (empty($mid) || empty($key) || empty($orderId)) {
            return [
                'success' => false,
                'gateway_order_id' => $orderId,
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => 'Missing Paytm verification parameters.',
            ];
        }

        $body = [
            'mid' => $mid,
            'orderId' => $orderId,
        ];
        $signature = hash_hmac('sha256', json_encode($body), $key);

        $baseUrl = $this->getBaseUrl($creds);
        $res = $this->httpRequest('POST', "{$baseUrl}/v3/order/status", [
            'body' => $body,
            'head' => ['signature' => $signature],
        ], ['Content-Type' => 'application/json']);

        $resBody = $res['data']['body'] ?? [];
        if (!empty($resBody['resultInfo']['resultStatus']) && $resBody['resultInfo']['resultStatus'] === 'TXN_SUCCESS') {
            return [
                'success' => true,
                'gateway_order_id' => $orderId,
                'transaction_id' => (string)($resBody['txnId'] ?? $orderId),
                'amount' => (string)($resBody['txnAmount'] ?? '0'),
                'currency' => 'INR',
                'raw_response' => $res['data'],
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $orderId,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'INR',
            'raw_response' => $res['data'] ?? [],
            'error' => $resBody['resultInfo']['resultMsg'] ?? 'Paytm transaction verification failed.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
