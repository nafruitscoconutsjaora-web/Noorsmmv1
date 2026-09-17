<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class OrderRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT o.*, s.name as service_name, s.min_quantity, s.max_quantity, 
                       u.username, u.email, p.name as provider_name 
                FROM `orders` o 
                JOIN `services` s ON o.service_id = s.id 
                JOIN `users` u ON o.user_id = u.id 
                LEFT JOIN `providers` p ON o.provider_id = p.id 
                WHERE o.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }

    public function findByIdForUpdate(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM `orders` WHERE `id` = :id FOR UPDATE", [':id' => $id]);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO `orders` 
                (`user_id`, `service_id`, `provider_id`, `provider_order_id`, `link`, 
                 `quantity`, `charge`, `status`, `provider_response`) 
                VALUES 
                (:user_id, :service_id, :provider_id, :provider_order_id, :link, 
                 :quantity, :charge, :status, :provider_response)";

        $this->db->execute($sql, [
            ':user_id' => $data['user_id'],
            ':service_id' => $data['service_id'],
            ':provider_id' => $data['provider_id'] ?? null,
            ':provider_order_id' => $data['provider_order_id'] ?? null,
            ':link' => $data['link'],
            ':quantity' => (int)$data['quantity'],
            ':charge' => $data['charge'],
            ':status' => $data['status'] ?? 'pending',
            ':provider_response' => $data['provider_response'] ?? null,
        ]);

        $orderId = (int)$this->db->lastInsertId();

        // Write to status history
        $this->addStatusHistory($orderId, 'none', $data['status'] ?? 'pending', 'Order placed by customer');

        return $orderId;
    }

    public function updateStatus(int $orderId, string $newStatus, ?string $note = null, string $createdBy = 'system'): bool
    {
        $order = $this->findById($orderId);
        if (!$order) {
            return false;
        }

        $oldStatus = $order['status'];
        if ($oldStatus === $newStatus) {
            return true;
        }

        $this->db->execute(
            "UPDATE `orders` SET `status` = :status, `updated_at` = NOW() WHERE `id` = :id",
            [':status' => $newStatus, ':id' => $orderId]
        );

        $this->addStatusHistory($orderId, $oldStatus, $newStatus, $note, $createdBy);
        return true;
    }

    public function updateProviderInfo(int $orderId, ?string $providerOrderId, ?string $response, ?string $errorMessage = null): void
    {
        $this->db->execute(
            "UPDATE `orders` SET `provider_order_id` = :p_id, `provider_response` = :resp, `error_message` = :err WHERE `id` = :id",
            [':p_id' => $providerOrderId, ':resp' => $response, ':err' => $errorMessage, ':id' => $orderId]
        );
    }

    public function updateCounters(int $orderId, ?int $startCounter, ?int $remains): void
    {
        $this->db->execute(
            "UPDATE `orders` SET `start_counter` = :sc, `remains` = :rem WHERE `id` = :id",
            [':sc' => $startCounter, ':rem' => $remains, ':id' => $orderId]
        );
    }

    public function addStatusHistory(int $orderId, string $oldStatus, string $newStatus, ?string $note = null, string $createdBy = 'system'): void
    {
        $this->db->execute(
            "INSERT INTO `order_status_history` (`order_id`, `old_status`, `new_status`, `note`, `created_by`) 
             VALUES (:order_id, :old, :new, :note, :created_by)",
            [
                ':order_id' => $orderId,
                ':old' => $oldStatus,
                ':new' => $newStatus,
                ':note' => $note,
                ':created_by' => $createdBy,
            ]
        );
    }

    public function getStatusHistory(int $orderId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `order_status_history` WHERE `order_id` = :id ORDER BY `id` ASC",
            [':id' => $orderId]
        );
    }

    public function getUserOrders(int $userId, int $page = 1, int $perPage = 20, string $status = '', string $search = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [':user_id' => $userId];
        $where = "WHERE o.user_id = :user_id";

        if ($status !== '' && $status !== 'all') {
            $where .= " AND o.status = :status";
            $params[':status'] = $status;
        }

        if ($search !== '') {
            $where .= " AND (s.name LIKE :s1 OR o.link LIKE :s2 OR o.id = :s3)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = is_numeric($search) ? (int)$search : 0;
        }

        $countRow = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM `orders` o JOIN `services` s ON o.service_id = s.id {$where}",
            $params
        );
        $total = (int)($countRow['total'] ?? 0);

        $sql = "SELECT o.*, s.name as service_name 
                FROM `orders` o 
                JOIN `services` s ON o.service_id = s.id 
                {$where} 
                ORDER BY o.id DESC 
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

    public function getAdminPaginated(int $page = 1, int $perPage = 25, string $status = '', string $search = '', ?int $userId = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = "WHERE 1=1";

        if ($status !== '' && $status !== 'all') {
            $where .= " AND o.status = :status";
            $params[':status'] = $status;
        }

        if ($userId !== null && $userId > 0) {
            $where .= " AND o.user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        if ($search !== '') {
            $where .= " AND (u.username LIKE :s1 OR o.link LIKE :s2 OR o.id = :s3 OR o.provider_order_id LIKE :s4)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = is_numeric($search) ? (int)$search : 0;
            $params[':s4'] = "%{$search}%";
        }

        $countRow = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM `orders` o JOIN `services` s ON o.service_id = s.id JOIN `users` u ON o.user_id = u.id {$where}",
            $params
        );
        $total = (int)($countRow['total'] ?? 0);

        $sql = "SELECT o.*, s.name as service_name, u.username, p.name as provider_name 
                FROM `orders` o 
                JOIN `services` s ON o.service_id = s.id 
                JOIN `users` u ON o.user_id = u.id 
                LEFT JOIN `providers` p ON o.provider_id = p.id 
                {$where} 
                ORDER BY o.id DESC 
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

    public function getPendingProviderOrders(int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT o.*, p.api_url, p.api_key, s.provider_service_id 
             FROM `orders` o 
             JOIN `providers` p ON o.provider_id = p.id 
             JOIN `services` s ON o.service_id = s.id 
             WHERE o.status IN ('pending', 'processing', 'in_progress') 
               AND o.provider_order_id IS NOT NULL 
               AND p.status = 'active' 
             ORDER BY o.id ASC 
             LIMIT {$limit}"
        );
    }

    public function getUnsentProviderOrders(int $limit = 20): array
    {
        return $this->db->fetchAll(
            "SELECT o.*, p.api_url, p.api_key, s.provider_service_id 
             FROM `orders` o 
             JOIN `providers` p ON o.provider_id = p.id 
             JOIN `services` s ON o.service_id = s.id 
             WHERE o.status = 'pending' 
               AND (o.provider_order_id IS NULL OR o.provider_order_id = '') 
               AND p.status = 'active' 
             ORDER BY o.id ASC 
             LIMIT {$limit}"
        );
    }

    public function getUserStats(int $userId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN `status` = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN `status` = 'processing' THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN `status` = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN `status` = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN `status` = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN `status` = 'refunded' THEN 1 ELSE 0 END) as refunded,
                    SUM(CASE WHEN `status` = 'failed' THEN 1 ELSE 0 END) as failed
                FROM `orders` WHERE `user_id` = :user_id";
        return $this->db->fetchOne($sql, [':user_id' => $userId]) ?: [];
    }

    public function getGlobalStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN `status` = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN `status` = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                    COALESCE(SUM(`charge`), 0) as total_spent
                FROM `orders`";
        return $this->db->fetchOne($sql) ?: [];
    }
}
