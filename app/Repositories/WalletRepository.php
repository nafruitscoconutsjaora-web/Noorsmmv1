<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class WalletRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function createTransaction(array $data): int
    {
        $orderId = $data['order_id'] ?? null;
        $paymentId = $data['payment_id'] ?? null;

        if (empty($orderId) && isset($data['reference_type']) && $data['reference_type'] === 'order') {
            $orderId = $data['reference_id'] ?? null;
        }
        if (empty($paymentId) && isset($data['reference_type']) && ($data['reference_type'] === 'payment' || $data['reference_type'] === 'deposit')) {
            $paymentId = $data['reference_id'] ?? null;
        }

        $sql = "INSERT INTO `wallet_transactions` 
                (`user_id`, `order_id`, `payment_id`, `amount`, `balance_before`, `balance_after`, `type`, `reference_id`, `description`, `status`) 
                VALUES 
                (:user_id, :order_id, :payment_id, :amount, :balance_before, :balance_after, :type, :reference_id, :description, :status)";

        $this->db->execute($sql, [
            ':user_id' => $data['user_id'],
            ':order_id' => $orderId,
            ':payment_id' => $paymentId,
            ':amount' => $data['amount'],
            ':balance_before' => $data['balance_before'],
            ':balance_after' => $data['balance_after'],
            ':type' => $data['type'],
            ':reference_id' => isset($data['reference_id']) ? (string)$data['reference_id'] : null,
            ':description' => $data['description'] ?? '',
            ':status' => $data['status'] ?? 'completed',
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getUserTransactions(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [':user_id' => $userId];

        $countRow = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM `wallet_transactions` WHERE `user_id` = :user_id",
            $params
        );
        $total = (int)($countRow['total'] ?? 0);

        $sql = "SELECT * FROM `wallet_transactions` 
                WHERE `user_id` = :user_id 
                ORDER BY `id` DESC 
                LIMIT {$perPage} OFFSET {$offset}";
        $items = $this->db->fetchAll($sql, $params);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int)ceil($total / max(1, $perPage)),
        ];
    }

    public function getAdminPaginated(int $page = 1, int $perPage = 25, ?int $userId = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = "WHERE 1=1";

        if ($userId !== null && $userId > 0) {
            $where .= " AND t.user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        $countRow = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM `wallet_transactions` t {$where}",
            $params
        );
        $total = (int)($countRow['total'] ?? 0);

        $sql = "SELECT t.*, u.username, u.email 
                FROM `wallet_transactions` t 
                JOIN `users` u ON t.user_id = u.id 
                {$where} 
                ORDER BY t.id DESC 
                LIMIT {$perPage} OFFSET {$offset}";
        $items = $this->db->fetchAll($sql, $params);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int)ceil($total / max(1, $perPage)),
        ];
    }
}
