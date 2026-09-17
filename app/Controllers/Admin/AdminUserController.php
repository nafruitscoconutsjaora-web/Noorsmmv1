<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\AuditLogRepository;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Services\WalletService;

class AdminUserController extends BaseController
{
    private UserRepository $userRepo;
    private WalletService $walletService;
    private WalletRepository $walletRepo;
    private OrderRepository $orderRepo;
    private AuditLogRepository $auditRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->walletService = new WalletService();
        $this->walletRepo = new WalletRepository();
        $this->orderRepo = new OrderRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    public function index(Request $request): Response
    {
        $page = max(1, (int)$request->query('page', 1));
        $search = trim((string)$request->query('search', ''));

        $users = $this->userRepo->getPaginated($page, 20, $search);

        return view('admin/users/index', [
            'users' => $users,
            'search' => $search,
        ], 'admin');
    }

    public function show(Request $request, string $id): Response
    {
        $userId = (int)$id;
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            Session::setFlash('error', 'User not found.');
            return $this->redirect('/admin/users');
        }

        $transactions = $this->walletRepo->getUserTransactions($userId, 1, 10);
        $orders = $this->orderRepo->getUserOrders($userId, 1, 10);

        return view('admin/users/show', [
            'user' => $user,
            'transactions' => $transactions,
            'orders' => $orders,
        ], 'admin');
    }

    public function adjustBalance(Request $request, string $id): Response
    {
        $userId = (int)$id;
        $type = (string)$request->post('type'); // credit or debit
        $amount = (string)$request->post('amount');
        $note = (string)$request->post('note', 'Manual balance adjustment by admin');

        if (!in_array($type, ['credit', 'debit'], true) || (float)$amount <= 0) {
            Session::setFlash('error', 'Invalid adjustment parameters.');
            return $this->redirect("/admin/users/{$userId}");
        }

        $admin = $this->admin();

        try {
            if ($type === 'credit') {
                $this->walletService->credit($userId, $amount, 'admin_adjustment', (int)$admin['id'], $note);
            } else {
                $this->walletService->debit($userId, $amount, 'admin_adjustment', (int)$admin['id'], $note);
            }

            $this->auditRepo->log((int)$admin['id'], 'adjust_user_balance', 'user', $userId, [
                'type' => $type,
                'amount' => $amount,
                'note' => $note,
            ], $request->ip());

            Session::setFlash('success', "User balance successfully updated ({$type} ₹{$amount}).");
        } catch (\Throwable $e) {
            Session::setFlash('error', "Balance update failed: " . $e->getMessage());
        }

        return $this->redirect("/admin/users/{$userId}");
    }

    public function updateStatus(Request $request, string $id): Response
    {
        $userId = (int)$id;
        $status = (string)$request->post('status');

        if (!in_array($status, ['active', 'suspended'], true)) {
            Session::setFlash('error', 'Invalid status.');
            return $this->redirect("/admin/users/{$userId}");
        }

        $this->userRepo->updateStatus($userId, $status);
        $admin = $this->admin();
        $this->auditRepo->log((int)$admin['id'], 'update_user_status', 'user', $userId, ['status' => $status], $request->ip());

        Session::setFlash('success', "User status changed to {$status}.");
        return $this->redirect("/admin/users/{$userId}");
    }
}
