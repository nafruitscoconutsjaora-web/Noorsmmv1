<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\PaymentRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Services\AuthService;
use App\Services\PaymentService;

class WalletController extends BaseController
{
    private PaymentService $paymentService;
    private WalletRepository $walletRepo;
    private PaymentRepository $paymentRepo;
    private UserRepository $userRepo;
    private AuthService $authService;

    public function __construct()
    {
        $this->paymentService = new PaymentService();
        $this->walletRepo = new WalletRepository();
        $this->paymentRepo = new PaymentRepository();
        $this->userRepo = new UserRepository();
        $this->authService = new AuthService();
    }

    public function index(Request $request): Response
    {
        $user = $this->user();
        $this->authService->refreshUserSession((int)$user['id']);
        $freshUser = $this->userRepo->findById((int)$user['id']);

        $page = max(1, (int)$request->query('page', 1));
        $transactions = $this->walletRepo->getUserTransactions((int)$user['id'], $page, 15);
        $payments = $this->paymentRepo->getUserPayments((int)$user['id'], 1, 10);

        return view('user/wallet/index', [
            'user' => $freshUser,
            'transactions' => $transactions,
            'payments' => $payments,
            'razorpay_key' => config('payments.razorpay.key_id') ?: setting('razorpay_key_id', ''),
        ], 'user');
    }

    public function initiatePayment(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'amount' => 'required|numeric|min:10',
        ]);

        $user = $this->user();
        $res = $this->paymentService->initiateRazorpay((int)$user['id'], (string)$data['amount'], 'INR');

        if ($request->isAjax()) {
            return $this->json($res);
        }

        return view('user/wallet/checkout', [
            'intent' => $res,
            'user' => $user,
        ], 'user');
    }

    public function verifyPayment(Request $request): Response
    {
        $gatewayOrderId = (string)$request->input('razorpay_order_id');
        $paymentId = (string)$request->input('razorpay_payment_id');
        $signature = (string)$request->input('razorpay_signature');

        if (empty($gatewayOrderId) || empty($paymentId)) {
            Session::setFlash('error', 'Payment verification failed: missing payment identifiers.');
            return $this->redirect('/wallet');
        }

        $success = $this->paymentService->verifyRazorpayPayment($gatewayOrderId, $paymentId, $signature);
        if ($success) {
            $user = $this->user();
            $this->authService->refreshUserSession((int)$user['id']);
            Session::setFlash('success', 'Payment successful! Funds have been credited to your wallet.');
        } else {
            Session::setFlash('error', 'Payment signature verification failed. If money was debited, please contact support.');
        }

        return $this->redirect('/wallet');
    }

    public function testDeposit(Request $request): Response
    {
        // Allowed only in non-production or test mode
        $mode = config('payments.razorpay.mode', 'test');
        if ($mode !== 'test') {
            Session::setFlash('error', 'Test deposits are disabled in live production.');
            return $this->redirect('/wallet');
        }

        $data = $this->validate($request->all(), [
            'amount' => 'required|numeric|min:10|max:50000',
        ]);

        $user = $this->user();
        $fakeOrderId = 'test_order_' . bin2hex(random_bytes(6));
        $fakeTxnId = 'pay_test_' . bin2hex(random_bytes(8));

        // Create pending payment
        $paymentDbId = $this->paymentRepo->create([
            'user_id' => $user['id'],
            'gateway' => 'razorpay_test',
            'transaction_id' => $fakeTxnId,
            'gateway_order_id' => $fakeOrderId,
            'amount' => $data['amount'],
            'fee' => '0.00000000',
            'currency' => 'INR',
            'status' => 'pending',
            'raw_payload' => json_encode(['test_mode' => true]),
        ]);

        // Credit to wallet
        $this->paymentService->creditPaymentToWallet($fakeOrderId, $fakeTxnId, ['test' => true]);
        $this->authService->refreshUserSession((int)$user['id']);

        Session::setFlash('success', "Test deposit of ₹" . number_format((float)$data['amount'], 2) . " credited successfully!");
        return $this->redirect('/wallet');
    }
}
