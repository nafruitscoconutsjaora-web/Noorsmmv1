<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class PayPalGateway extends AbstractGateway
{
    protected string $code = 'paypal';
    protected string $name = 'PayPal';
    protected string $category = 'international';
    protected string $defaultCurrency = 'USD';
    protected array $supportedCurrencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'SGD', 'JPY'];

    public function getCredentialFields(): array
    {
        return [
            'client_id' => [
                'label' => 'Client ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'Enter PayPal REST Client ID',
                'help' => 'From developer.paypal.com dashboard.',
            ],
            'client_secret' => [
                'label' => 'Client Secret',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter PayPal REST Client Secret',
                'help' => 'Secret key for OAuth token generation.',
            ],
            'mode' => [
                'label' => 'Mode',
                'type' => 'select',
                'required' => true,
                'options' => [
                    'live' => 'Live (api-m.paypal.com)',
                    'sandbox' => 'Sandbox (api-m.sandbox.paypal.com)',
                ],
                'help' => 'PayPal environment mode.',
            ],
        ];
    }

    private function getBaseUrl(array $creds): string
    {
        return ($creds['mode'] ?? 'live') === 'sandbox'
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    private function getAccessToken(array $creds): ?string
    {
        $clientId = trim((string)($creds['client_id'] ?? ''));
        $secret = trim((string)($creds['client_secret'] ?? ''));
        $baseUrl = $this->getBaseUrl($creds);

        $headers = [
            'Authorization: Basic ' . base64_encode("{$clientId}:{$secret}"),
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/v1/oauth2/token", 'grant_type=client_credentials', $headers);

        return $res['success'] && !empty($res['data']['access_token']) ? (string)$res['data']['access_token'] : null;
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $token = $this->getAccessToken($creds);

        if (!$token) {
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'PayPal OAuth authorization failed. Check Client ID and Secret.',
            ];
        }

        $currency = strtoupper($paymentData['currency'] ?? 'USD');
        $amount = number_format((float)$paymentData['payable_amount'], 2, '.', '');
        $baseUrl = $this->getBaseUrl($creds);

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $paymentData['order_id'],
                    'description' => 'Wallet Deposit #' . $paymentData['order_id'],
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => $amount,
                    ],
                ],
            ],
            'application_context' => [
                'brand_name' => config('app.name', 'SMM Panel'),
                'landing_page' => 'BILLING',
                'user_action' => 'PAY_NOW',
                'return_url' => $paymentData['return_url'],
                'cancel_url' => $paymentData['cancel_url'],
            ],
        ];

        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ];

        $res = $this->httpRequest('POST', "{$baseUrl}/v2/checkout/orders", $payload, $headers);

        if ($res['success'] && !empty($res['data']['id']) && !empty($res['data']['links'])) {
            $approveUrl = null;
            foreach ($res['data']['links'] as $link) {
                if (($link['rel'] ?? '') === 'approve') {
                    $approveUrl = $link['href'];
                    break;
                }
            }

            if ($approveUrl) {
                return [
                    'success' => true,
                    'action_type' => 'redirect',
                    'redirect_url' => $approveUrl,
                    'gateway_order_id' => (string)$res['data']['id'],
                    'checkout_data' => $res['data'],
                    'message' => 'PayPal order initialized.',
                ];
            }
        }

        $err = $res['data']['message'] ?? $res['error'] ?? 'PayPal order creation failure.';
        Logger::error("PayPal order error: {$err}", [], 'payments');
        return [
            'success' => false,
            'action_type' => 'instructions',
            'error' => 'Unable to initialize PayPal transaction: ' . $err,
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $orderId = (string)$request->input('token', $request->input('order_id', ''));

        if (empty($orderId)) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'USD',
                'raw_response' => $request->all(),
                'error' => 'Missing PayPal order token.',
            ];
        }

        $token = $this->getAccessToken($creds);
        if (!$token) {
            return [
                'success' => false,
                'gateway_order_id' => $orderId,
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'USD',
                'raw_response' => $request->all(),
                'error' => 'Failed to obtain PayPal authentication token.',
            ];
        }

        $baseUrl = $this->getBaseUrl($creds);
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ];

        // Attempt order capture
        $res = $this->httpRequest('POST', "{$baseUrl}/v2/checkout/orders/{$orderId}/capture", '{}', $headers);

        // If order already captured, fetch order status
        if (!$res['success'] && ($res['status'] === 422 || ($res['data']['name'] ?? '') === 'ORDER_ALREADY_CAPTURED')) {
            $res = $this->httpRequest('GET', "{$baseUrl}/v2/checkout/orders/{$orderId}", null, $headers);
        }

        if ($res['success'] && !empty($res['data']['status']) && $res['data']['status'] === 'COMPLETED') {
            $capture = $res['data']['purchase_units'][0]['payments']['captures'][0] ?? [];
            $amount = (string)($capture['amount']['value'] ?? $res['data']['purchase_units'][0]['amount']['value'] ?? '0');
            $currency = (string)($capture['amount']['currency_code'] ?? 'USD');
            $captureId = (string)($capture['id'] ?? $orderId);

            return [
                'success' => true,
                'gateway_order_id' => $orderId,
                'transaction_id' => $captureId,
                'amount' => $amount,
                'currency' => $currency,
                'raw_response' => $res['data'],
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => $orderId,
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'USD',
            'raw_response' => $res['data'] ?? [],
            'error' => 'PayPal order capture failed or incomplete.',
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        $rawPayload = (string)file_get_contents('php://input');
        $event = json_decode($rawPayload, true);

        if (!$event || empty($event['event_type'])) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'USD',
                'status' => 'failed',
                'raw_payload' => $rawPayload,
                'error' => 'Malformed PayPal webhook.',
            ];
        }

        if ($event['event_type'] === 'PAYMENT.CAPTURE.COMPLETED') {
            $resource = $event['resource'] ?? [];
            $orderId = (string)($resource['supplementary_data']['related_ids']['order_id'] ?? $resource['id'] ?? '');

            return [
                'success' => true,
                'gateway_order_id' => $orderId,
                'transaction_id' => (string)($resource['id'] ?? ''),
                'amount' => (string)($resource['amount']['value'] ?? '0'),
                'currency' => (string)($resource['amount']['currency_code'] ?? 'USD'),
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
            'currency' => 'USD',
            'status' => 'pending',
            'raw_payload' => $event,
            'error' => 'Ignored event: ' . $event['event_type'],
        ];
    }
}
