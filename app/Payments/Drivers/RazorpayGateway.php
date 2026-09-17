<?php

declare(strict_types=1);

namespace App\Payments\Drivers;

use App\Payments\Contracts\PaymentGatewayInterface;
use App\Support\HttpClient;
use App\Support\Logger;
use App\Exceptions\AppException;

class RazorpayGateway implements PaymentGatewayInterface
{
    private string $keyId;
    private string $keySecret;
    private string $webhookSecret;
    private HttpClient $http;

    public function __construct(
        ?string $keyId = null,
        ?string $keySecret = null,
        ?string $webhookSecret = null,
        ?HttpClient $http = null
    ) {
        $this->keyId = $keyId ?? (config('payments.razorpay.key_id') ?: (string)setting('razorpay_key_id'));
        $this->keySecret = $keySecret ?? (config('payments.razorpay.key_secret') ?: (string)setting('razorpay_key_secret'));
        $this->webhookSecret = $webhookSecret ?? (config('payments.razorpay.webhook_secret') ?: (string)setting('razorpay_webhook_secret'));
        $this->http = $http ?? new HttpClient(30);
    }

    public function getName(): string
    {
        return 'razorpay';
    }

    public function isConfigured(): bool
    {
        return !empty($this->keyId) && !empty($this->keySecret);
    }

    /**
     * Initiate Razorpay Order
     */
    public function initiatePayment(int $userId, string $amount, string $currency = 'INR', array $metadata = []): array
    {
        $amountInPaise = (int)round((float)$amount * 100);
        $receipt = 'rcpt_' . time() . '_' . $userId;

        if ($this->isConfigured()) {
            $authHeader = base64_encode("{$this->keyId}:{$this->keySecret}");
            $res = $this->http->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amountInPaise,
                'currency' => $currency,
                'receipt' => $receipt,
                'payment_capture' => 1,
            ], [
                'Authorization' => "Basic {$authHeader}",
                'Content-Type' => 'application/json',
            ]);

            if (isset($res['data']['id'])) {
                return [
                    'gateway' => 'razorpay',
                    'gateway_order_id' => (string)$res['data']['id'],
                    'amount_paise' => $amountInPaise,
                    'currency' => $currency,
                    'key_id' => $this->keyId,
                    'is_live' => true,
                ];
            }

            Logger::error('Razorpay order creation failed: ' . ($res['body'] ?? 'Unknown error'), [], 'payments');
            throw new AppException('Razorpay order initiation failed: ' . ($res['data']['error']['description'] ?? 'API error'));
        }

        // Demo / Sandbox mode fallback
        $gatewayOrderId = 'order_sim_' . bin2hex(random_bytes(8));
        return [
            'gateway' => 'razorpay',
            'gateway_order_id' => $gatewayOrderId,
            'amount_paise' => $amountInPaise,
            'currency' => $currency,
            'key_id' => $this->keyId ?: 'rzp_test_simulated_key',
            'is_live' => false,
        ];
    }

    /**
     * Verify frontend checkout payment signature
     */
    public function verifyPayment(array $payload): bool
    {
        $orderId = $payload['razorpay_order_id'] ?? '';
        $paymentId = $payload['razorpay_payment_id'] ?? '';
        $signature = $payload['razorpay_signature'] ?? '';

        if (empty($orderId) || empty($paymentId) || empty($signature)) {
            return false;
        }

        if (!empty($this->keySecret)) {
            $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->keySecret);
            return hash_equals($expectedSignature, $signature);
        }

        // In demo / test mode without secret, allow successful verification
        return true;
    }

    /**
     * Handle incoming asynchronous webhook
     */
    public function handleWebhook(string $rawPayload, string $signatureHeader): array
    {
        if (!empty($this->webhookSecret)) {
            $expectedSignature = hash_hmac('sha256', $rawPayload, $this->webhookSecret);
            if (!hash_equals($expectedSignature, $signatureHeader)) {
                Logger::error('Razorpay webhook HMAC signature mismatch', [], 'payments');
                return ['verified' => false];
            }
        }

        $data = json_decode($rawPayload, true);
        if (!$data || !isset($data['event'])) {
            return ['verified' => false];
        }

        if ($data['event'] === 'payment.captured' || $data['event'] === 'order.paid') {
            $payment = $data['payload']['payment']['entity'] ?? [];
            return [
                'verified' => true,
                'event' => $data['event'],
                'gateway_order_id' => (string)($payment['order_id'] ?? ''),
                'transaction_id' => (string)($payment['id'] ?? ''),
                'amount' => isset($payment['amount']) ? (string)($payment['amount'] / 100) : '0.00',
                'currency' => $payment['currency'] ?? 'INR',
                'payload' => $data,
            ];
        }

        return [
            'verified' => true,
            'event' => $data['event'],
            'gateway_order_id' => '',
            'transaction_id' => '',
            'payload' => $data,
        ];
    }
}
