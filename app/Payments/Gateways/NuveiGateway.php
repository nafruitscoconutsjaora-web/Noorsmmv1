<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class NuveiGateway extends AbstractGateway
{
    protected string $code = 'nuvei';
    protected string $name = 'Nuvei / SafeCharge';
    protected string $category = 'international';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'EUR', 'GBP', 'CAD'];

    public function getCredentialFields(): array
    {
        return [
            'merchant_id' => [
                'label' => 'Merchant ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Nuvei Merchant ID',
                'help' => 'Assigned Nuvei Merchant ID.',
            ],
            'merchant_site_id' => [
                'label' => 'Merchant Site ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Merchant Site ID',
                'help' => 'Assigned Site ID.',
            ],
            'secret_key' => [
                'label' => 'Secret Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Nuvei Secret Key',
                'help' => 'Secret Key for SHA-256 checksums.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (secure.safecharge.com)',
                    'integration' => 'Integration Sandbox (ppp-test.safecharge.com)',
                ],
                'help' => 'Nuvei environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'production') === 'integration'
            ? 'https://ppp-test.safecharge.com/ppp/api/v1'
            : 'https://secure.safecharge.com/ppp/api/v1';
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $mid = trim((string)($creds['merchant_id'] ?? ''));
        $siteId = trim((string)($creds['merchant_site_id'] ?? ''));
        $secret = trim((string)($creds['secret_key'] ?? ''));

        if (empty($mid) || empty($siteId) || empty($secret)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Nuvei credentials are not configured.',
            ];
        }

        $orderId = 'NUV_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $currency = strtoupper($paymentData['currency'] ?? 'USD');
        $timeStamp = date('YmdHis');
        $baseUrl = $this->getBaseUrl($creds);

        // Checksum: merchantIdmerchantSiteIdclientRequestIdamountcurrencytimeStampsecretKey
        $clientRequestId = 'REQ_' . bin2hex(random_bytes(6));
        $checksum = hash('sha256', "{$mid}{$siteId}{$clientRequestId}{$amount}{$currency}{$timeStamp}{$secret}");

        $payload = [
            'merchantId' => $mid,
            'merchantSiteId' => $siteId,
            'clientRequestId' => $clientRequestId,
            'amount' => $amount,
            'currency' => $currency,
            'userTokenId' => (string)($paymentData['user']['id'] ?? '1'),
            'clientUniqueId' => $orderId,
            'timeStamp' => $timeStamp,
            'checksum' => $checksum,
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/openOrder.do", $payload, ['Content-Type: application/json']);

        if ($res['success'] && !empty($res['data']['sessionToken'])) {
            return [
                'success' => true,
                'action_type' => 'sdk',
                'gateway_order_id' => (string)($res['data']['orderId'] ?? $orderId),
                'checkout_data' => [
                    'session_token' => $res['data']['sessionToken'],
                    'merchant_id' => $mid,
                    'merchant_site_id' => $siteId,
                    'order_id' => $orderId,
                ],
                'message' => 'Nuvei session token generated.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Nuvei session open failed: ' . ($res['data']['errorReason'] ?? $res['error'] ?? 'Error'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $status = (string)$request->input('status', $request->input('transactionStatus', ''));
        $orderId = (string)$request->input('clientUniqueId', $request->input('orderId', ''));

        if (in_array(strtoupper($status), ['APPROVED', 'SUCCESS'])) {
            return [
                'success' => true,
                'gateway_order_id' => $orderId,
                'transaction_id' => (string)$request->input('transactionId', $orderId),
                'amount' => (string)$request->input('totalAmount', '0'),
                'currency' => (string)$request->input('currency', 'USD'),
                'raw_response' => $request->all(),
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $orderId,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'USD',
            'raw_response' => $request->all(),
            'error' => 'Nuvei transaction status: ' . ($status ?: 'pending'),
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
