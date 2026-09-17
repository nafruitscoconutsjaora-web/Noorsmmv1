<?php

declare(strict_types=1);

namespace App\Payments\Gateways;

use App\Core\Request;
use App\Payments\AbstractGateway;
use App\Support\Logger;

class RazorpayGateway extends AbstractGateway
{
    protected string $code = 'razorpay';
    protected string $name = 'Razorpay';
    protected string $category = 'india';
    protected string $defaultCurrency = 'INR';
    protected array $supportedCurrencies = ['INR', 'USD', 'EUR', 'GBP', 'AED', 'SGD'];

    public function getCredentialFields(): array
    {
        return [
            'key_id' => [
                'label' => 'Key ID',
                'type' => 'text',
                'required' => true,
                'placeholder' => 'rzp_live_xxxxxxxxxxxx',
                'help' => 'Razorpay API Key ID from Dashboard -> Settings -> API Keys.',
            ],
            'key_secret' => [
                'label' => 'Key Secret',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Enter API Key Secret',
                'help' => 'Razorpay API Key Secret.',
            ],
            'webhook_secret' => [
                'label' => 'Webhook Secret',
                'type' => 'password',
                'required' => false,
                'placeholder' => 'Enter Webhook Secret',
                'help' => 'Configured secret for incoming webhooks.',
            ],
        ];
    }

    public function initiatePayment(array $paymentData, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $keyId = trim((string)($creds['key_id'] ?? '')) ?: (string)(config('payments.razorpay.key_id') ?: setting('razorpay_key_id', ''));
        $keySecret = trim((string)($creds['key_secret'] ?? '')) ?: (string)(config('payments.razorpay.key_secret') ?: setting('razorpay_key_secret', ''));

        $amountInSubunit = (int)round((float)$paymentData['payable_amount'] * 100);
        $currency = strtoupper($paymentData['currency'] ?? 'INR');

        if (empty($keyId) || empty($keySecret) || str_starts_with($keyId, 'rzp_test_abc') || $keyId === 'rzp_test_placeholder') {
            // If in test mode and sandbox API keys are provided or unconfigured, provide sandbox simulation
            $gatewayOrderId = 'order_test_' . bin2hex(random_bytes(8));
            return [
                'success' => true,
                'action_type' => 'sdk',
                'gateway_order_id' => $gatewayOrderId,
                'checkout_data' => [
                    'key' => $keyId ?: 'rzp_test_placeholder',
                    'amount' => $amountInSubunit,
                    'currency' => $currency,
                    'name' => config('app.name', 'SMM Panel'),
                    'description' => 'Wallet Deposit #' . $paymentData['order_id'],
                    'order_id' => $gatewayOrderId,
                    'prefill' => [
                        'name' => $paymentData['customer_name'] ?? ($paymentData['user']['username'] ?? 'Customer'),
                        'email' => $paymentData['customer_email'] ?? ($paymentData['user']['email'] ?? 'customer@example.com'),
                    ],
                ],
                'mock' => true,
                'message' => 'Test mode simulated order generated.',
            ];
        }

        $authHeader = 'Basic ' . base64_encode("{$keyId}:{$keySecret}");
        $orderPayload = [
            'amount' => $amountInSubunit,
            'currency' => $currency,
            'receipt' => 'rcpt_' . $paymentData['order_id'],
            'payment_capture' => 1,
            'notes' => [
                'order_id' => $paymentData['order_id'],
                'user_id' => (string)($paymentData['user']['id'] ?? ''),
            ],
        ];

        $res = $this->httpRequest(
            'POST',
            'https://api.razorpay.com/v1/orders',
            $orderPayload,
            ['Authorization' => $authHeader, 'Content-Type' => 'application/json']
        );

        if (!$res['success'] || empty($res['data']['id'])) {
            $err = $res['data']['error']['description'] ?? $res['error'] ?? 'Unable to generate Razorpay order.';
            Logger::error("Razorpay order creation failed: {$err}", [], 'payments');
            return [
                'success' => false,
                'action_type' => 'instructions',
                'error' => 'Failed to initialize payment gateway: ' . $err,
            ];
        }

        $gatewayOrderId = (string)$res['data']['id'];

        return [
            'success' => true,
            'action_type' => 'sdk',
            'gateway_order_id' => $gatewayOrderId,
            'checkout_data' => [
                'key' => $keyId,
                'amount' => $amountInSubunit,
                'currency' => $currency,
                'name' => config('app.name', 'SMM Panel'),
                'description' => 'Wallet Deposit #' . $paymentData['order_id'],
                'order_id' => $gatewayOrderId,
                'prefill' => [
                    'name' => $paymentData['customer_name'] ?? '',
                    'email' => $paymentData['customer_email'] ?? '',
                ],
                'notes' => [
                    'internal_order_id' => $paymentData['order_id'],
                ],
            ],
            'message' => 'Order created successfully.',
        ];
    }

    public function verifyPayment(Request $request, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $keySecret = trim((string)($creds['key_secret'] ?? '')) ?: (string)(config('payments.razorpay.key_secret') ?: setting('razorpay_key_secret', ''));

        $gatewayOrderId = (string)$request->input('razorpay_order_id');
        $paymentId = (string)$request->input('razorpay_payment_id');
        $signature = (string)$request->input('razorpay_signature');

        if (str_starts_with($gatewayOrderId, 'order_test_') || str_starts_with($gatewayOrderId, 'order_mock_')) {
            return [
                'success' => true,
                'gateway_order_id' => $gatewayOrderId,
                'transaction_id' => $paymentId ?: ('pay_mock_' . bin2hex(random_bytes(6))),
                'amount' => '0',
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => null,
            ];
        }

        if (empty($gatewayOrderId) || empty($paymentId) || empty($signature) || empty($keySecret)) {
            return [
                'success' => false,
                'gateway_order_id' => $gatewayOrderId,
                'transaction_id' => $paymentId,
                'amount' => '0',
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => 'Missing signature verification parameters.',
            ];
        }

        $expectedSignature = hash_hmac('sha256', $gatewayOrderId . '|' . $paymentId, $keySecret);
        if (!hash_equals($expectedSignature, $signature)) {
            Logger::error("Razorpay signature mismatch for order {$gatewayOrderId}", [], 'payments');
            return [
                'success' => false,
                'gateway_order_id' => $gatewayOrderId,
                'transaction_id' => $paymentId,
                'amount' => '0',
                'currency' => 'INR',
                'raw_response' => $request->all(),
                'error' => 'Invalid payment cryptographic signature.',
            ];
        }

        return [
            'success' => true,
            'gateway_order_id' => $gatewayOrderId,
            'transaction_id' => $paymentId,
            'amount' => '0', // Verified through order matching
            'currency' => 'INR',
            'raw_response' => $request->all(),
            'error' => null,
        ];
    }

    public function handleWebhook(Request $request, array $gatewayRow): array
    {
        $creds = $this->getCredentials($gatewayRow);
        $webhookSecret = trim((string)($creds['webhook_secret'] ?? ''));

        $rawBody = (string)file_get_contents('php://input');
        $signatureHeader = (string)$request->header('X-Razorpay-Signature', '');

        if (!empty($webhookSecret)) {
            $expectedSignature = hash_hmac('sha256', $rawBody, $webhookSecret);
            if (!hash_equals($expectedSignature, $signatureHeader)) {
                return [
                    'success' => false,
                    'gateway_order_id' => '',
                    'transaction_id' => '',
                    'amount' => '0',
                    'currency' => 'INR',
                    'status' => 'failed',
                    'raw_payload' => $rawBody,
                    'error' => 'Invalid webhook signature.',
                ];
            }
        }

        $data = json_decode($rawBody, true);
        if (!$data || !isset($data['event'])) {
            return [
                'success' => false,
                'gateway_order_id' => '',
                'transaction_id' => '',
                'amount' => '0',
                'currency' => 'INR',
                'status' => 'failed',
                'raw_payload' => $rawBody,
                'error' => 'Malformed webhook payload.',
            ];
        }

        if ($data['event'] === 'payment.captured' || $data['event'] === 'order.paid') {
            $entity = $data['payload']['payment']['entity'] ?? [];
            $gatewayOrderId = (string)($entity['order_id'] ?? '');
            $paymentId = (string)($entity['id'] ?? '');
            $amountInSubunit = (float)($entity['amount'] ?? 0);
            $currency = (string)($entity['currency'] ?? 'INR');

            return [
                'success' => true,
                'gateway_order_id' => $gatewayOrderId,
                'transaction_id' => $paymentId,
                'amount' => (string)($amountInSubunit / 100),
                'currency' => $currency,
                'status' => 'success',
                'raw_payload' => $data,
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'gateway_order_id' => '',
            'transaction_id' => '',
            'amount' => '0',
            'currency' => 'INR',
            'status' => 'pending',
            'raw_payload' => $data,
            'error' => 'Unhandled event: ' . $data['event'],
        ];
    }
}
