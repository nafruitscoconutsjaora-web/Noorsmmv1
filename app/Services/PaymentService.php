<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Exceptions\AppException;
use App\Repositories\PaymentRepository;
use App\Repositories\UserRepository;
use App\Support\HttpClient;
use App\Support\Logger;
use App\Support\Money;

class PaymentService
{
    private Database $db;
    private PaymentRepository $paymentRepo;
    private UserRepository $userRepo;
    private WalletService $walletService;
    private HttpClient $http;

    public function __construct(
        ?Database $db = null,
        ?PaymentRepository $paymentRepo = null,
        ?UserRepository $userRepo = null,
        ?WalletService $walletService = null,
        ?HttpClient $http = null
    ) {
        $this->db = $db ?? Database::getInstance();
        $this->paymentRepo = $paymentRepo ?? new PaymentRepository($this->db);
        $this->userRepo = $userRepo ?? new UserRepository($this->db);
        $this->walletService = $walletService ?? new WalletService($this->db);
        $this->http = $http ?? new HttpClient(30);
    }

    /**
     * Create a payment intent / order for Razorpay
     */
    public function initiateRazorpay(int $userId, string $amount, string $currency = 'INR'): array
    {
        if (Money::lt($amount, '10')) {
            throw new AppException('Minimum deposit amount is ₹10.00');
        }

        $keyId = config('payments.razorpay.key_id') ?: setting('razorpay_key_id');
        $keySecret = config('payments.razorpay.key_secret') ?: setting('razorpay_key_secret');

        $isLiveConfigured = !empty($keyId) && !empty($keySecret);

        // Convert amount to paise for Razorpay (e.g. ₹100.00 = 10000 paise)
        $amountInPaise = (int)round((float)$amount * 100);
        $gatewayOrderId = 'order_' . bin2hex(random_bytes(10));

        if ($isLiveConfigured) {
            $authHeader = base64_encode("{$keyId}:{$keySecret}");
            $res = $this->http->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amountInPaise,
                'currency' => $currency,
                'receipt' => 'rcpt_' . time() . '_' . $userId,
                'payment_capture' => 1,
            ], [
                'Authorization' => "Basic {$authHeader}",
                'Content-Type' => 'application/json',
            ]);

            if (isset($res['data']['id'])) {
                $gatewayOrderId = (string)$res['data']['id'];
            } else {
                Logger::error("Razorpay order creation failed: " . ($res['body'] ?? 'Unknown error'), [], 'payments');
                throw new AppException("Payment gateway error: " . ($res['data']['error']['description'] ?? 'Unable to create order.'));
            }
        }

        $paymentId = $this->paymentRepo->create([
            'user_id' => $userId,
            'gateway' => 'razorpay',
            'transaction_id' => null,
            'gateway_order_id' => $gatewayOrderId,
            'amount' => $amount,
            'fee' => '0.00000000',
            'currency' => $currency,
            'status' => 'pending',
            'raw_payload' => null,
        ]);

        return [
            'payment_db_id' => $paymentId,
            'gateway_order_id' => $gatewayOrderId,
            'amount' => $amount,
            'amount_paise' => $amountInPaise,
            'currency' => $currency,
            'key_id' => $keyId,
            'is_configured' => $isLiveConfigured,
        ];
    }

    /**
     * Verify payment signature callback from client
     */
    public function verifyRazorpayPayment(string $gatewayOrderId, string $razorpayPaymentId, string $signature): bool
    {
        $keySecret = config('payments.razorpay.key_secret') ?: setting('razorpay_key_secret');

        if (!empty($keySecret)) {
            $expectedSignature = hash_hmac('sha256', $gatewayOrderId . '|' . $razorpayPaymentId, $keySecret);
            if (!hash_equals($expectedSignature, $signature)) {
                Logger::error("Razorpay signature verification failed", [
                    'order_id' => $gatewayOrderId,
                    'payment_id' => $razorpayPaymentId,
                ], 'payments');
                return false;
            }
        }

        return $this->creditPaymentToWallet($gatewayOrderId, $razorpayPaymentId, ['signature' => $signature]);
    }

    /**
     * Handle Razorpay Webhook notification
     */
    public function handleRazorpayWebhook(string $payload, string $signatureHeader): bool
    {
        $webhookSecret = config('payments.razorpay.webhook_secret') ?: setting('razorpay_webhook_secret');

        if (!empty($webhookSecret)) {
            $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
            if (!hash_equals($expectedSignature, $signatureHeader)) {
                Logger::error("Razorpay webhook signature verification failed", [], 'payments');
                return false;
            }
        }

        $data = json_decode($payload, true);
        if (!$data || !isset($data['event'])) {
            return false;
        }

        if ($data['event'] === 'payment.captured' || $data['event'] === 'order.paid') {
            $paymentEntity = $data['payload']['payment']['entity'] ?? [];
            $gatewayOrderId = $paymentEntity['order_id'] ?? '';
            $razorpayPaymentId = $paymentEntity['id'] ?? '';

            if ($gatewayOrderId && $razorpayPaymentId) {
                return $this->creditPaymentToWallet($gatewayOrderId, $razorpayPaymentId, $data);
            }
        }

        return true;
    }

    /**
     * Idempotently credit funds to user balance upon verified payment
     */
    public function creditPaymentToWallet(string $gatewayOrderId, string $transactionId, array $rawPayload): bool
    {
        return $this->db->transaction(function () use ($gatewayOrderId, $transactionId, $rawPayload) {
            $payment = $this->paymentRepo->findByGatewayOrderIdForUpdate($gatewayOrderId);
            if (!$payment) {
                Logger::error("Payment not found for order: {$gatewayOrderId}", [], 'payments');
                return false;
            }

            // Prevent double crediting (Idempotency check)
            if ($payment['status'] === 'completed') {
                Logger::info("Payment {$gatewayOrderId} already credited. Skipping.", [], 'payments');
                return true;
            }

            // Update payment record to completed
            $this->paymentRepo->updateStatus(
                (int)$payment['id'],
                'completed',
                $transactionId,
                json_encode($rawPayload)
            );

            // Credit user wallet
            $this->walletService->credit(
                (int)$payment['user_id'],
                $payment['amount'],
                'deposit',
                (int)$payment['id'],
                "Deposit via Razorpay (Txn: {$transactionId})"
            );

            Logger::info("Successfully credited ₹{$payment['amount']} to user #{$payment['user_id']}", [
                'payment_id' => $payment['id'],
                'txn' => $transactionId,
            ], 'payments');

            return true;
        });
    }
}
