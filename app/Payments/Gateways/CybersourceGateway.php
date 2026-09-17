<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class CybersourceGateway extends AbstractGateway
{
    protected string $code = 'cybersource';
    protected string $name = 'CyberSource (Visa)';
    protected string $category = 'international';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'SGD'];

    public function getCredentialFields(): array
    {
        return [
            'merchant_id' => [
                'label' => 'Merchant ID (Organization ID)',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter CyberSource Merchant ID',
                'help' => 'Assigned Merchant ID.',
            ],
            'key_id' => [
                'label' => 'REST Shared Key ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Key ID',
                'help' => 'From CyberSource Payment Configuration -> Keys.',
            ],
            'secret_key' => [
                'label' => 'REST Shared Secret Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Secret Key',
                'help' => 'Base64 encoded shared secret.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (api.cybersource.com)',
                    'test' => 'Test Sandbox (apitest.cybersource.com)',
                ],
                'help' => 'CyberSource environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['environment'] ?? 'production') === 'test'
            ? 'https://apitest.cybersource.com'
            : 'https://api.cybersource.com';
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $mid = trim((string)($creds['merchant_id'] ?? ''));
        $keyId = trim((string)($creds['key_id'] ?? ''));
        $secret = trim((string)($creds['secret_key'] ?? ''));

        if (empty($mid) || empty($keyId) || empty($secret)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'CyberSource credentials are not configured.',
            ];
        }

        $orderId = 'CS_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $currency = strtoupper($paymentData['currency'] ?? 'USD');
        $baseUrl = $this->getBaseUrl($creds);
        $resource = '/pts/v2/payments';

        $payload = [
            'clientReferenceInformation' => [
                'code' => $orderId,
            ],
            'orderInformation' => [
                'amountDetails' => [
                    'totalAmount' => $amount,
                    'currency' => $currency,
                ],
                'billTo' => [
                    'firstName' => $paymentData['customer_name'] ?? 'Customer',
                    'lastName' => 'User',
                    'email' => $paymentData['customer_email'] ?? 'customer@example.com',
                ],
            ],
        ];

        $jsonPayload = json_encode($payload);
        $digest = 'SHA-256=' . base64_encode(hash('sha256', $jsonPayload, true));
        $date = gmdate('D, d M Y H:i:s T');
        $host = parse_url($baseUrl, PHP_URL_HOST);

        $signatureString = "host: {$host}\ndate: {$date}\n(request-target): post {$resource}\ndigest: {$digest}\nv-c-merchant-id: {$mid}";
        $signature = base64_encode(hash_hmac('sha256', $signatureString, base64_decode($secret), true));
        $sigHeader = "keyid=\"{$keyId}\", algorithm=\"HmacSHA256\", headers=\"host date (request-target) digest v-c-merchant-id\", signature=\"{$signature}\"";

        $headers = [
            'v-c-merchant-id: ' . $mid,
            'Date: ' . $date,
            'Host: ' . $host,
            'Digest: ' . $digest,
            'Signature: ' . $sigHeader,
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}{$resource}", $jsonPayload, $headers);

        if ($res['success'] && in_array(strtoupper($res['data']['status'] ?? ''), ['AUTHORIZED', 'PENDING_AUTHENTICATION'])) {
            $redirectUrl = $res['data']['consumerAuthenticationInformation']['pareqUrl'] ?? null;
            if ($redirectUrl) {
                return [
                    'success' => true,
                    'action_type' => 'redirect',
                    'redirect_url' => $redirectUrl,
                    'gateway_order_id' => (string)($res['data']['id'] ?? $orderId),
                    'checkout_data' => $res['data'],
                    'message' => 'CyberSource 3DS redirect required.',
                ];
            }

            return [
                'success' => true,
                'action_type' => 'sdk',
                'gateway_order_id' => (string)($res['data']['id'] ?? $orderId),
                'checkout_data' => $res['data'],
                'message' => 'CyberSource transaction authorized.',
            ];
        }

        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'CyberSource authorization failed: ' . ($res['data']['message'] ?? $res['error'] ?? 'Connection error'),
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $id = (string)$request->input('id', $request->input('order_id', ''));
        return [
            'success' => true,
            'gateway_order_id' => $id,
            'transaction_id' => $id,
            'amount' => '0',
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
