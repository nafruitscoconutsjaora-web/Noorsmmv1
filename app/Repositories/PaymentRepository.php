<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class PaymentRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO `payments` 
                (`user_id`, `gateway`, `transaction_id`, `gateway_order_id`, `amount`, `fee`, `currency`, `status`, `raw_payload`) 
                VALUES 
                (:user_id, :gateway, :transaction_id, :gateway_order_id, :amount, :fee, :currency, :status, :raw_payload)";

        $this->db->execute($sql, [
            ':user_id' => $data['user_id'],
            ':gateway' => $data['gateway'],
            ':transaction_id' => $data['transaction_id'] ?? null,
            ':gateway_order_id' => $data['gateway_order_id'] ?? null,
            ':amount' => $data['amount'],
            ':fee' => $data['fee'] ?? '0.00000000',
            ':currency' => $data['currency'] ?? 'INR',
            ':status' => $data['status'] ?? 'pending',
            ':raw_payload' => $data['raw_payload'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM `payments` WHERE `id` = :id", [':id' => $id]);
    }

    public function findByGatewayOrderId(string $gatewayOrderId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM `payments` WHERE `gateway_order_id` = :id",
            [':id' => $gatewayOrderId]
        );
    }

    public function findByGatewayOrderIdForUpdate(string $gatewayOrderId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM `payments` WHERE `gateway_order_id` = :id FOR UPDATE",
            [':id' => $gatewayOrderId]
        );
    }

    public function findByTransactionId(string $txnId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM `payments` WHERE `transaction_id` = :id",
            [':id' => $txnId]
        );
    }

    public function updateStatus(int $id, string $status, ?string $transactionId = null, ?string $rawPayload = null): bool
    {
        $params = [':status' => $status, ':id' => $id];
        $sql = "UPDATE `payments` SET `status` = :status, `updated_at` = NOW()";

        if ($transactionId !== null) {
            $sql .= ", `transaction_id` = :txn";
            $params[':txn'] = $transactionId;
        }
        if ($rawPayload !== null) {
            $sql .= ", `raw_payload` = :raw";
            $params[':raw'] = $rawPayload;
        }
        $sql .= " WHERE `id` = :id";

        return $this->db->execute($sql, $params) > 0;
    }

    public function getUserPayments(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [':user_id' => $userId];

        $countRow = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM `payments` WHERE `user_id` = :user_id",
            $params
        );
        $total = (int)($countRow['total'] ?? 0);

        $sql = "SELECT * FROM `payments` 
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

    public function getAdminPaginated(int $page = 1, int $perPage = 25, string $status = '', string $search = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = "WHERE 1=1";

        if ($status !== '' && $status !== 'all') {
            $where .= " AND p.status = :status";
            $params[':status'] = $status;
        }

        if ($search !== '') {
            $where .= " AND (u.username LIKE :s1 OR p.gateway_order_id LIKE :s2 OR p.transaction_id LIKE :s3)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
        }

        $countRow = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM `payments` p JOIN `users` u ON p.user_id = u.id {$where}",
            $params
        );
        $total = (int)($countRow['total'] ?? 0);

        $sql = "SELECT p.*, u.username, u.email 
                FROM `payments` p 
                JOIN `users` u ON p.user_id = u.id 
                {$where} 
                ORDER BY p.id DESC 
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
