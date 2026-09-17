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
        $orderId = $data['order_id'] ?? $data['gateway_order_id'] ?? ('ord_' . bin2hex(random_bytes(10)));
        $paymentId = $data['payment_id'] ?? $data['transaction_id'] ?? null;
        $payload = $data['payload'] ?? $data['raw_payload'] ?? null;

        $sql = "INSERT INTO `payments` 
                (`user_id`, `gateway`, `order_id`, `payment_id`, `signature`, `amount`, `currency`, `fee`, `bonus`, `wallet_credit`, `status`, `payload`) 
                VALUES 
                (:user_id, :gateway, :order_id, :payment_id, :signature, :amount, :currency, :fee, :bonus, :wallet_credit, :status, :payload)";

        $this->db->execute($sql, [
            ':user_id' => $data['user_id'],
            ':gateway' => $data['gateway'],
            ':order_id' => $orderId,
            ':payment_id' => $paymentId,
            ':signature' => $data['signature'] ?? null,
            ':amount' => $data['amount'],
            ':currency' => $data['currency'] ?? 'INR',
            ':fee' => $data['fee'] ?? '0.00000000',
            ':bonus' => $data['bonus'] ?? '0.00000000',
            ':wallet_credit' => $data['wallet_credit'] ?? ($data['amount'] ?? '0.00000000'),
            ':status' => $data['status'] ?? 'pending',
            ':payload' => is_array($payload) ? json_encode($payload) : $payload,
        ]);

        return (int)$this->db->lastInsertId();
    }

    private function mapAliases(?array $row): ?array
    {
        if (!$row) {
            return null;
        }
        $row['gateway_order_id'] = $row['order_id'] ?? '';
        $row['transaction_id'] = $row['payment_id'] ?? '';
        $row['raw_payload'] = $row['payload'] ?? '';
        return $row;
    }

    public function findById(int $id): ?array
    {
        return $this->mapAliases($this->db->fetchOne("SELECT * FROM `payments` WHERE `id` = :id", [':id' => $id]));
    }

    public function findByGatewayOrderId(string $gatewayOrderId, ?string $fallbackId = null): ?array
    {
        $payment = $this->mapAliases($this->db->fetchOne(
            "SELECT * FROM `payments` WHERE `order_id` = :id1 OR `payment_id` = :id2",
            [':id1' => $gatewayOrderId, ':id2' => $gatewayOrderId]
        ));

        if (!$payment && $fallbackId) {
            $payment = $this->mapAliases($this->db->fetchOne(
                "SELECT * FROM `payments` WHERE `order_id` = :id1 OR `payment_id` = :id2",
                [':id1' => $fallbackId, ':id2' => $fallbackId]
            ));
        }

        if (!$payment) {
            $payment = $this->mapAliases($this->db->fetchOne(
                "SELECT * FROM `payments` WHERE JSON_UNQUOTE(JSON_EXTRACT(`payload`, '$.gateway_order_id')) = :id",
                [':id' => $gatewayOrderId]
            ));
        }

        return $payment;
    }

    public function findByGatewayOrderIdForUpdate(string $gatewayOrderId, ?string $fallbackId = null): ?array
    {
        $payment = $this->mapAliases($this->db->fetchOne(
            "SELECT * FROM `payments` WHERE `order_id` = :id1 OR `payment_id` = :id2 FOR UPDATE",
            [':id1' => $gatewayOrderId, ':id2' => $gatewayOrderId]
        ));

        if (!$payment && $fallbackId) {
            $payment = $this->mapAliases($this->db->fetchOne(
                "SELECT * FROM `payments` WHERE `order_id` = :id1 OR `payment_id` = :id2 FOR UPDATE",
                [':id1' => $fallbackId, ':id2' => $fallbackId]
            ));
        }

        if (!$payment) {
            $payment = $this->mapAliases($this->db->fetchOne(
                "SELECT * FROM `payments` WHERE JSON_UNQUOTE(JSON_EXTRACT(`payload`, '$.gateway_order_id')) = :id FOR UPDATE",
                [':id' => $gatewayOrderId]
            ));
        }

        return $payment;
    }

    public function findByTransactionId(string $txnId): ?array
    {
        return $this->mapAliases($this->db->fetchOne(
            "SELECT * FROM `payments` WHERE `payment_id` = :id1 OR `order_id` = :id2",
            [':id1' => $txnId, ':id2' => $txnId]
        ));
    }

    public function updateStatus(int $id, string $status, ?string $transactionId = null, ?string $rawPayload = null): bool
    {
        $params = [':status' => $status, ':id' => $id];
        $sql = "UPDATE `payments` SET `status` = :status, `updated_at` = NOW()";

        if ($transactionId !== null) {
            $sql .= ", `payment_id` = :txn";
            $params[':txn'] = $transactionId;
        }
        if ($rawPayload !== null) {
            $sql .= ", `payload` = :raw";
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
