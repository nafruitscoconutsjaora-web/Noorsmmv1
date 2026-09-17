<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

class AdminWalletManagementController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request): Response
    {
        $page = max(1, (int)$request->query('page', 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        // Fetch transactions with user info
        $sql = "SELECT wt.*, u.username, u.email 
                FROM `wallet_transactions` wt 
                JOIN `users` u ON wt.user_id = u.id 
                ORDER BY wt.created_at DESC 
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = (int)$this->db->query("SELECT COUNT(*) FROM `wallet_transactions`")->fetchColumn();
        $totalPages = (int)ceil($total / $perPage);

        // Fetch all users for manual adjustment modal/form
        $users = $this->db->query("SELECT id, username, balance FROM `users` WHERE `status` = 'active' ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

        $statsRow = $this->db->query("SELECT 
            COALESCE(SUM(balance), 0) as total_user_balance,
            COUNT(*) as total_users,
            COALESCE(AVG(balance), 0) as avg_balance
            FROM `users`")->fetch(PDO::FETCH_ASSOC);

        $stats = [
            'total_user_balance' => (float)($statsRow['total_user_balance'] ?? 0),
            'total_users' => (int)($statsRow['total_users'] ?? 0),
            'avg_balance' => (float)($statsRow['avg_balance'] ?? 0),
        ];

        return view('admin/wallets/index', [
            'transactions' => $transactions,
            'users' => $users,
            'stats' => $stats,
            'page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
        ], 'admin');
    }

    public function adjust(Request $request): Response
    {
        $userId = (int)$request->input('user_id');
        $action = (string)$request->input('action'); // credit or debit
        $amount = (float)$request->input('amount');
        $reason = trim((string)$request->input('reason', 'Admin adjustment'));
        $admin = $this->admin();

        if ($userId <= 0 || $amount <= 0 || !in_array($action, ['credit', 'debit'])) {
            flash('error', 'Invalid adjustment parameters.');
            return $this->redirect('/admin/wallets');
        }

        $this->db->beginTransaction();
        try {
            $stmtUser = $this->db->prepare("SELECT balance FROM `users` WHERE `id` = :id FOR UPDATE");
            $stmtUser->execute([':id' => $userId]);
            $balBefore = (string)$stmtUser->fetchColumn();

            if ($action === 'debit' && bccomp($balBefore, (string)$amount, 8) < 0) {
                $this->db->rollBack();
                flash('error', 'User has insufficient balance for debit deduction.');
                return $this->redirect('/admin/wallets');
            }

            $balAfter = $action === 'credit'
                ? bcadd($balBefore, (string)$amount, 8)
                : bcsub($balBefore, (string)$amount, 8);

            // Update user balance
            $stmtUp = $this->db->prepare("UPDATE `users` SET `balance` = :bal, `updated_at` = NOW() WHERE `id` = :id");
            $stmtUp->execute([':bal' => $balAfter, ':id' => $userId]);

            // Insert transaction
            $stmtTx = $this->db->prepare("INSERT INTO `wallet_transactions` 
                (`user_id`, `type`, `amount`, `balance_before`, `balance_after`, `description`, `reference_id`, `status`, `created_at`) 
                VALUES (:uid, :type, :amt, :before, :after, :desc, :ref, 'completed', NOW())");
            $stmtTx->execute([
                ':uid' => $userId,
                ':type' => $action,
                ':amt' => $amount,
                ':before' => $balBefore,
                ':after' => $balAfter,
                ':desc' => "[Manual Adjustment by Admin #{$admin['id']}] {$reason}",
                ':ref' => 'ADM-' . time(),
            ]);

            $this->db->commit();
            flash('success', "Successfully performed {$action} of ₹" . number_format($amount, 2) . " on user account.");
        } catch (\Throwable $e) {
            $this->db->rollBack();
            flash('error', 'Failed to adjust balance: ' . $e->getMessage());
        }

        return $this->redirect('/admin/wallets');
    }
}
