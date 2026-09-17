<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class BraintreeGateway extends AbstractGateway
{
    protected string $code = 'braintree';
    protected string $name = 'Braintree (PayPal Service)';
    protected string $category = 'international';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'EUR', 'GBP', 'AUD', 'CAD'];

    public function getCredentialFields(): array
    {
        return [
            'merchant_id' => [
                'label' => 'Merchant ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Braintree Merchant ID',
                'help' => 'Assigned Braintree Merchant ID.',
            ],
            'public_key' => [
                'label' => 'Public Key',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter Public Key',
                'help' => 'Braintree API Public Key.',
            ],
            'private_key' => [
                'label' => 'Private Key',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter Private Key',
                'help' => 'Braintree API Private Key.',
            ],
            'environment' => [
                'label' => 'Environment',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'production' => 'Production (api.braintreegateway.com)',
                    'sandbox' => 'Sandbox (api.sandbox.braintreegateway.com)',
                ],
                'help' => 'Braintree environment.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        $mid = $creds['merchant_id'] ?? '';
        return ($creds['environment'] ?? 'production') === 'sandbox'
            ? "https://api.sandbox.braintreegateway.com/merchants/{$mid}"
            : "https://api.braintreegateway.com/merchants/{$mid}";
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $mid = trim((string)($creds['merchant_id'] ?? ''));
        $pub = trim((string)($creds['public_key'] ?? ''));
        $priv = trim((string)($creds['private_key'] ?? ''));

        if (empty($mid) || empty($pub) || empty($priv)) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Braintree credentials are not configured.',
            ];
        }

        $orderId = 'BT_' . $paymentData['order_id'];
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $currency = strtoupper($paymentData['currency'] ?? 'USD');

        // Generate client token request
        $baseUrl = $this->getBaseUrl($creds);
        $headers = [
            'Authorization: Basic ' . base64_encode("{$pub}:{$priv}"),
            'Content-Type: application/xml',
            'Accept: application/xml',
        ];

        $xml = '<client_token><version>2</version></client_token>';
        $res = $this->httpRequest('POST', "{$baseUrl}/client_token", $xml, $headers);

        $clientToken = null;
        if ($res['success'] && !empty($res['raw']) && preg_match('/<value>(.*?)<\/value>/', $res['raw'], $matches)) {
            $clientToken = $matches[1];
        }

        return [
            'success' => true,
            'action_type' => 'sdk',
            'gateway_order_id' => $orderId,
            'checkout_data' => [
                'client_token' => $clientToken,
                'amount' => $amount,
                'currency' => $currency,
                'order_id' => $orderId,
            ],
            'message' => 'Braintree client session prepared.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $nonce = (string)$request->input('payment_method_nonce');
        $orderId = (string)$request->input('order_id');

        if (empty($nonce) || empty($orderId)) {
            return [
                'success' => false,
                'gateway_order_id' => $orderId,
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'USD',
                'raw_response' => $request->all(),
                'error' => 'Missing Braintree payment method nonce.',
            ];
        }

        $creds = $this->getCredentials($gatewayRow);
        $pub = trim((string)($creds['public_key'] ?? ''));
        $priv = trim((string)($creds['private_key'] ?? ''));
        $amount = (string)$request->input('amount', '10.00');

        $baseUrl = $this->getBaseUrl($creds);
        $xml = "<transaction>
            <type>sale</type>
            <amount>{$amount}</amount>
            <payment-method-nonce>{$nonce}</payment-method-nonce>
            <order-id>{$orderId}</order-id>
            <options><submit-for-settlement>true</submit-for-settlement></options>
        </transaction>";

        $headers = [
            'Authorization: Basic ' . base64_encode("{$pub}:{$priv}"),
            'Content-Type: application/xml',
            'Accept: application/xml',
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/transactions", $xml, $headers);

        if ($res['success'] && preg_match('/<status>(settled|submitted_for_settlement|authorized)<\/status>/', $res['raw'] ?? '')) {
            preg_match('/<id>(.*?)<\/id>/', $res['raw'], $idMatch);
            $txnId = $idMatch[1] ?? $orderId;
            return [
                'success' => true,
                'gateway_order_id' => $orderId,
                'transaction_id' => $txnId,
                'amount' => $amount,
                'currency' => 'USD',
                'raw_response' => $res['raw'],
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $orderId,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'USD',
            'raw_response' => $res['raw'] ?? [],
            'error' => 'Braintree transaction settlement failed.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        return $this->verifyPayment($request, $gatewayRow);
    }
}
