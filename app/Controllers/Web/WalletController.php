<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Exceptions\AppException;
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
        $activeGateways = $this->paymentService->getActiveGateways();

        return view('user/wallet/index', [
            'user' => $freshUser,
            'transactions' => $transactions,
            'payments' => $payments,
            'activeGateways' => $activeGateways,
            'razorpay_key' => config('payments.razorpay.key_id') ?: setting('razorpay_key_id', ''),
        ], 'user');
    }

    public function initiatePayment(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'amount' => 'required|numeric|min:1',
        ]);

        $gateway = strtolower(trim((string)$request->input('gateway', 'razorpay')));
        $user = $this->user();

        try {
            $res = $this->paymentService->initiatePayment((int)$user['id'], $gateway, (float)$data['amount']);
        } catch (AppException $e) {
            if ($request->isAjax()) {
                return $this->json(['success' => false, 'error' => $e->getMessage()], 400);
            }
            Session::setFlash('error', $e->getMessage());
            return $this->redirect('/wallet');
        }

        if ($request->isAjax()) {
            return $this->json($res);
        }

        // Handle action types
        $actionType = $res['action_type'] ?? 'sdk';

        if ($actionType === 'redirect' && !empty($res['redirect_url'])) {
            return $this->redirect($res['redirect_url']);
        }

        return view('user/wallet/checkout', [
            'intent' => $res,
            'gateway' => $gateway,
            'user' => $user,
        ], 'user');
    }

    public function verifyPayment(Request $request): Response
    {
        $gateway = (string)($request->input('gateway') ?: $request->query('gateway', ''));
        if (empty($gateway)) {
            if ($request->has('razorpay_order_id') || $request->has('razorpay_payment_id')) {
                $gateway = 'razorpay';
            } else {
                $gateway = 'razorpay';
            }
        }

        $success = $this->paymentService->verifyPayment($gateway, $request);
        if ($success) {
            $user = $this->user();
            $this->authService->refreshUserSession((int)$user['id']);
            Session::setFlash('success', 'Payment verified successfully! Funds have been credited to your wallet balance.');
        } else {
            Session::setFlash('error', 'Payment verification was not successful. If money was debited, it will be credited automatically or please contact support.');
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
            'order_id' => $fakeOrderId,
            'payment_id' => $fakeTxnId,
            'amount' => $data['amount'],
            'fee' => '0.00000000',
            'bonus' => '0.00000000',
            'wallet_credit' => $data['amount'],
            'currency' => 'INR',
            'status' => 'pending',
            'payload' => json_encode(['test_mode' => true]),
        ]);

        // Credit to wallet
        $this->paymentService->creditPaymentToWallet($fakeOrderId, $fakeTxnId, ['test' => true]);
        $this->authService->refreshUserSession((int)$user['id']);

        Session::setFlash('success', "Test deposit of ₹" . number_format((float)$data['amount'], 2) . " credited successfully!");
        return $this->redirect('/wallet');
    }
}
