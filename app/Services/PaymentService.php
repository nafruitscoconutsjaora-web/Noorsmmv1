<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Exceptions\AppException;
use App\Payments\PaymentManager;
use App\Repositories\PaymentRepository;
use App\Repositories\UserRepository;
use App\Support\Logger;
use App\Support\Money;

class PaymentService
{
    private Database $db;
    private PaymentRepository $paymentRepo;
    private UserRepository $userRepo;
    private WalletService $walletService;

    public function __construct(
        ?Database $db = null,
        ?PaymentRepository $paymentRepo = null,
        ?UserRepository $userRepo = null,
        ?WalletService $walletService = null
    ) {
        $this->db = $db ?? Database::getInstance();
        $this->paymentRepo = $paymentRepo ?? new PaymentRepository($this->db);
        $this->userRepo = $userRepo ?? new UserRepository($this->db);
        $this->walletService = $walletService ?? new WalletService($this->db);
    }

    /**
     * Get active payment gateways available for user checkout.
     */
    public function getActiveGateways(?string $category = null): array
    {
        return PaymentManager::getActiveGateways($category);
    }

    /**
     * Initiate a deposit via any supported gateway adapter.
     */
    public function initiatePayment(int $userId, string $gatewayCode, float $amount, ?string $currency = null): array
    {
        $gatewayRow = PaymentManager::getGatewayRow($gatewayCode);
        if (!$gatewayRow || (empty($gatewayRow['is_enabled']) && empty($gatewayRow['is_active']))) {
            throw new AppException('The selected payment gateway is currently unavailable or disabled.');
        }

        // Calculate fee, bonus, payable amount, and wallet credit
        $calculations = PaymentManager::calculateAmounts($gatewayRow, $amount);
        if ($calculations['error'] !== null) {
            throw new AppException($calculations['error']);
        }

        $user = $this->userRepo->findById($userId);
        if (!$user) {
            throw new AppException('User account not found.');
        }

        $appUrl = rtrim(config('app.url', 'http://localhost:3000'), '/');
        $currencyCode = $currency ?: ($gatewayRow['currency'] ?? 'INR');
        $orderId = 'ORD_' . time() . '_' . $userId . '_' . bin2hex(random_bytes(4));

        // Create pending payment record
        $paymentDbId = $this->paymentRepo->create([
            'user_id' => $userId,
            'gateway' => $gatewayCode,
            'order_id' => $orderId,
            'amount' => (string)$amount,
            'fee' => (string)$calculations['fee'],
            'bonus' => (string)$calculations['bonus'],
            'wallet_credit' => (string)$calculations['wallet_credit'],
            'currency' => $currencyCode,
            'status' => 'pending',
            'raw_payload' => json_encode([
                'initiated_at' => date('Y-m-d H:i:s'),
                'calculations' => $calculations,
            ]),
        ]);

        $paymentData = [
            'order_id' => $orderId,
            'payment_db_id' => $paymentDbId,
            'amount' => $amount,
            'payable_amount' => $calculations['payable_amount'],
            'wallet_credit' => $calculations['wallet_credit'],
            'fee' => $calculations['fee'],
            'bonus' => $calculations['bonus'],
            'currency' => $currencyCode,
            'user' => $user,
            'return_url' => $appUrl . '/wallet/verify?gateway=' . urlencode($gatewayCode) . '&order_id=' . urlencode($orderId),
            'cancel_url' => $appUrl . '/wallet',
            'notify_url' => $appUrl . '/webhook/' . urlencode($gatewayCode),
        ];

        // Call gateway adapter
        $initResult = PaymentManager::initiate($gatewayCode, $paymentData);

        if (empty($initResult['success'])) {
            $err = $initResult['error'] ?? 'Unable to initiate payment with the selected gateway.';
            Logger::error("Payment initiation failed for [{$gatewayCode}]: {$err}", ['user_id' => $userId, 'order_id' => $orderId], 'payments');
            throw new AppException($err);
        }

        // If adapter provided a specific gateway order id, update it and preserve in payload
        if (!empty($initResult['gateway_order_id']) && $initResult['gateway_order_id'] !== $orderId) {
            $existing = $this->paymentRepo->findById($paymentDbId);
            $pData = is_string($existing['payload'] ?? null) ? json_decode($existing['payload'], true) : ($existing['payload'] ?? []);
            if (!is_array($pData)) {
                $pData = [];
            }
            $pData['gateway_order_id'] = $initResult['gateway_order_id'];

            $this->paymentRepo->updateStatus(
                $paymentDbId,
                'pending',
                $initResult['gateway_order_id'],
                json_encode($pData)
            );
        }

        return array_merge($initResult, [
            'payment_db_id' => $paymentDbId,
            'order_id' => $orderId,
            'gateway_code' => $gatewayCode,
            'gateway_name' => $gatewayRow['name'],
            'requested_amount' => $amount,
            'payable_amount' => $calculations['payable_amount'],
            'wallet_credit' => $calculations['wallet_credit'],
            'fee' => $calculations['fee'],
            'bonus' => $calculations['bonus'],
            'currency' => $currencyCode,
        ]);
    }

    /**
     * Verify payment return from client or gateway redirect.
     */
    public function verifyPayment(string $gatewayCode, Request $request): bool
    {
        $verification = PaymentManager::verify($gatewayCode, $request);

        if (!$verification['success']) {
            Logger::error("Payment verification failed for [{$gatewayCode}]: " . ($verification['error'] ?? 'Unknown error'), [
                'request' => $request->all(),
            ], 'payments');
            return false;
        }

        $orderId = $verification['gateway_order_id'] ?: (string)$request->input('order_id', '');
        $txnId = $verification['transaction_id'] ?: ('txn_' . bin2hex(random_bytes(8)));
        $internalOrderId = (string)$request->input('order_id', '');

        if (empty($orderId) && empty($internalOrderId)) {
            Logger::error("Payment verification missing order_id for [{$gatewayCode}]", [], 'payments');
            return false;
        }

        return $this->creditPaymentToWallet($orderId, $txnId, $verification['raw_response'] ?? [], $internalOrderId);
    }

    /**
     * Handle incoming asynchronous webhook from gateway.
     */
    public function handleWebhook(string $gatewayCode, Request $request): bool
    {
        $webhookResult = PaymentManager::webhook($gatewayCode, $request);

        // Record webhook log in database for complete auditability
        try {
            $this->db->execute(
                "INSERT INTO payment_webhook_logs (gateway, event_type, event_id, payload, signature, status, created_at) 
                 VALUES (:gateway, :event_type, :event_id, :payload, :signature, :status, NOW())",
                [
                    ':gateway' => $gatewayCode,
                    ':event_type' => $webhookResult['event_type'] ?? 'payment_notification',
                    ':event_id' => $webhookResult['event_id'] ?? null,
                    ':payload' => json_encode($webhookResult['raw_payload'] ?? $request->all()),
                    ':signature' => (string)($request->header('X-Razorpay-Signature') ?: $request->header('X-Signature') ?: $request->header('Stripe-Signature') ?: ''),
                    ':status' => $webhookResult['success'] ? 'verified' : 'invalid',
                ]
            );
        } catch (\Throwable $e) {
            Logger::error("Failed to log payment webhook: " . $e->getMessage(), [], 'payments');
        }

        if (!$webhookResult['success']) {
            return false;
        }

        // If status is completed/success, credit wallet
        if (in_array($webhookResult['status'] ?? '', ['completed', 'success', 'paid'], true)) {
            $orderId = $webhookResult['gateway_order_id'] ?? '';
            $txnId = $webhookResult['transaction_id'] ?? ('wh_txn_' . bin2hex(random_bytes(6)));
            $internalOrderId = $webhookResult['order_id'] ?? (string)$request->input('order_id', '');

            if (!empty($orderId) || !empty($internalOrderId)) {
                return $this->creditPaymentToWallet($orderId ?: $internalOrderId, $txnId, $webhookResult['raw_payload'] ?? [], $internalOrderId);
            }
        }

        return true;
    }

    /**
     * Atomically and idempotently credit funds to user balance upon verified payment.
     */
    public function creditPaymentToWallet(string $gatewayOrderId, string $transactionId, array $rawPayload, ?string $fallbackOrderId = null): bool
    {
        return $this->db->transaction(function () use ($gatewayOrderId, $transactionId, $rawPayload, $fallbackOrderId) {
            $payment = $this->paymentRepo->findByGatewayOrderIdForUpdate($gatewayOrderId, $fallbackOrderId);
            if (!$payment) {
                Logger::error("Payment not found for order: {$gatewayOrderId}", [], 'payments');
                return false;
            }

            // Prevent double crediting (Idempotency check)
            if (in_array($payment['status'], ['completed', 'success'], true)) {
                Logger::info("Payment {$gatewayOrderId} already credited. Skipping duplicate credit.", [], 'payments');
                return true;
            }

            // Preserve payload metadata including gateway_order_id and completed_at
            $pData = is_string($payment['payload'] ?? null) ? json_decode($payment['payload'], true) : ($payment['payload'] ?? []);
            if (!is_array($pData)) {
                $pData = [];
            }
            $pData['gateway_order_id'] = $gatewayOrderId;
            $pData['verification_payload'] = $rawPayload;
            $pData['completed_at'] = date('Y-m-d H:i:s');

            // Update payment record to completed
            $this->paymentRepo->updateStatus(
                (int)$payment['id'],
                'completed',
                $transactionId,
                json_encode($pData)
            );

            // Determine credit amount: wallet_credit (which includes any bonus) or base amount
            $creditAmount = (float)($payment['wallet_credit'] ?? 0);
            if ($creditAmount <= 0) {
                $creditAmount = (float)$payment['amount'];
            }

            $bonus = (float)($payment['bonus'] ?? 0);
            $gatewayName = ucfirst((string)$payment['gateway']);
            $desc = "Deposit via {$gatewayName} (Txn: {$transactionId})";
            if ($bonus > 0) {
                $desc .= " [Bonus: +₹" . number_format($bonus, 2) . "]";
            }

            // Credit user wallet
            $this->walletService->credit(
                (int)$payment['user_id'],
                (string)$creditAmount,
                'deposit',
                (int)$payment['id'],
                $desc
            );

            Logger::info("Successfully credited ₹{$creditAmount} to user #{$payment['user_id']} for order {$gatewayOrderId}", [
                'payment_id' => $payment['id'],
                'txn' => $transactionId,
                'base_amount' => $payment['amount'],
                'bonus' => $bonus,
            ], 'payments');

            return true;
        });
    }

    /**
     * Backward-compatible Razorpay initiation wrapper
     */
    public function initiateRazorpay(int $userId, string $amount, string $currency = 'INR'): array
    {
        $res = $this->initiatePayment($userId, 'razorpay', (float)$amount, $currency);

        $checkout = $res['checkout_data'] ?? [];
        return [
            'payment_db_id' => $res['payment_db_id'],
            'gateway_order_id' => $res['gateway_order_id'] ?? $res['order_id'],
            'amount' => $res['requested_amount'],
            'amount_paise' => $checkout['amount'] ?? (int)round((float)$amount * 100),
            'currency' => $res['currency'],
            'key_id' => $checkout['key'] ?? (config('payments.razorpay.key_id') ?: setting('razorpay_key_id', '')),
            'is_configured' => true,
        ];
    }

    /**
     * Backward-compatible Razorpay payment verification
     */
    public function verifyRazorpayPayment(string $gatewayOrderId, string $razorpayPaymentId, string $signature): bool
    {
        $request = new Request([
            'razorpay_order_id' => $gatewayOrderId,
            'razorpay_payment_id' => $razorpayPaymentId,
            'razorpay_signature' => $signature,
            'order_id' => $gatewayOrderId,
        ]);

        return $this->verifyPayment('razorpay', $request);
    }

    /**
     * Backward-compatible Razorpay webhook handler
     */
    public function handleRazorpayWebhook(string $payload, string $signatureHeader): bool
    {
        $request = new Request([], [], [], [], [], [
            'HTTP_X_RAZORPAY_SIGNATURE' => $signatureHeader,
        ], $payload);

        return $this->handleWebhook('razorpay', $request);
    }
}
