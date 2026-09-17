<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

class AdminReconciliationController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request): Response
    {
        // Fetch recent payments with user data
        $sql = "SELECT p.*, p.gateway as method, COALESCE(p.payment_id, p.order_id) as transaction_id, 
                       (p.amount - COALESCE(p.fee, 0)) as net_amount, u.username, u.email 
                FROM `payments` p 
                JOIN `users` u ON p.user_id = u.id 
                ORDER BY p.created_at DESC 
                LIMIT 50";
        $payments = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        // Fetch webhook logs if any
        $webhooks = $this->db->query("SELECT * FROM `payment_webhook_logs` ORDER BY `created_at` DESC LIMIT 25")->fetchAll(PDO::FETCH_ASSOC);

        // Total reconciled vs pending
        $reconciliationStats = $this->db->query("SELECT 
            COUNT(*) as total_payments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_payments,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_payments,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
            COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_volume
            FROM `payments`")->fetch(PDO::FETCH_ASSOC);

        return view('admin/payments/reconciliation', [
            'payments' => $payments,
            'webhooks' => $webhooks,
            'stats' => $reconciliationStats,
        ], 'admin');
    }

    public function reconcile(Request $request, string $id): Response
    {
        $paymentId = (int)$id;

        $stmt = $this->db->prepare("SELECT * FROM `payments` WHERE `id` = :id");
        $stmt->execute([':id' => $paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            flash('error', 'Payment not found.');
            return $this->redirect('/admin/payments/reconciliation');
        }

        if ($payment['status'] === 'completed') {
            flash('error', 'Payment is already marked as completed.');
            return $this->redirect('/admin/payments/reconciliation');
        }

        // Complete the payment and credit user wallet
        $this->db->beginTransaction();
        try {
            $stmtUp = $this->db->prepare("UPDATE `payments` SET `status` = 'completed', `updated_at` = NOW() WHERE `id` = :id");
            $stmtUp->execute([':id' => $paymentId]);

            // Credit user wallet
            $stmtUser = $this->db->prepare("SELECT balance FROM `users` WHERE `id` = :id FOR UPDATE");
            $stmtUser->execute([':id' => $payment['user_id']]);
            $balBefore = (string)$stmtUser->fetchColumn();
            $balAfter = bcadd($balBefore, (string)$payment['amount'], 8);

            $this->db->prepare("UPDATE `users` SET `balance` = :bal WHERE `id` = :id")
                ->execute([':bal' => $balAfter, ':id' => $payment['user_id']]);

            $this->db->prepare("INSERT INTO `wallet_transactions` 
                (`user_id`, `type`, `amount`, `balance_before`, `balance_after`, `description`, `reference_id`, `status`, `created_at`) 
                VALUES (:uid, 'credit', :amt, :before, :after, :desc, :ref, 'completed', NOW())")
                ->execute([
                    ':uid' => $payment['user_id'],
                    ':amt' => $payment['amount'],
                    ':before' => $balBefore,
                    ':after' => $balAfter,
                    ':desc' => "Payment Reconciled (Manual Approval #{$paymentId})",
                    ':ref' => $payment['payment_id'] ?? $payment['order_id'],
                ]);

            $this->db->commit();
            flash('success', "Payment #{$paymentId} successfully reconciled and credited to user balance.");
        } catch (\Throwable $e) {
            $this->db->rollBack();
            flash('error', 'Reconciliation failed: ' . $e->getMessage());
        }

        return $this->redirect('/admin/payments/reconciliation');
    }
}
