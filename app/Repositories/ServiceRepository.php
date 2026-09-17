<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class ServiceRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function getAllActiveWithCategory(): array
    {
        $sql = "SELECT s.*, c.name as category_name, c.icon as category_icon, p.name as provider_name 
                FROM `services` s 
                JOIN `categories` c ON s.category_id = c.id 
                LEFT JOIN `providers` p ON s.provider_id = p.id 
                WHERE s.status = 'active' AND c.status = 'active'
                ORDER BY c.sort_order ASC, s.sort_order ASC, s.id ASC";
        return $this->db->fetchAll($sql);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT s.*, c.name as category_name, p.name as provider_name 
                FROM `services` s 
                JOIN `categories` c ON s.category_id = c.id 
                LEFT JOIN `providers` p ON s.provider_id = p.id 
                WHERE s.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }

    public function findByIdForUpdate(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM `services` WHERE `id` = :id FOR UPDATE", [':id' => $id]);
    }

    public function getAdminPaginated(int $page = 1, int $perPage = 25, string $search = '', ?int $categoryId = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = "WHERE 1=1";

        if ($search !== '') {
            $where .= " AND (s.name LIKE :s1 OR s.id = :s2)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = is_numeric($search) ? (int)$search : 0;
        }

        if ($categoryId !== null && $categoryId > 0) {
            $where .= " AND s.category_id = :cat_id";
            $params[':cat_id'] = $categoryId;
        }

        $countRow = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM `services` s JOIN `categories` c ON s.category_id = c.id {$where}",
            $params
        );
        $total = (int)($countRow['total'] ?? 0);

        $sql = "SELECT s.*, c.name as category_name, p.name as provider_name 
                FROM `services` s 
                JOIN `categories` c ON s.category_id = c.id 
                LEFT JOIN `providers` p ON s.provider_id = p.id 
                {$where} 
                ORDER BY s.id DESC 
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

    public function create(array $data): int
    {
        $sql = "INSERT INTO `services` 
                (`category_id`, `provider_id`, `provider_service_id`, `name`, `description`, `service_type`, 
                 `provider_cost`, `provider_currency`, `margin_type`, `margin_value`, `rate`, 
                 `min_quantity`, `max_quantity`, `drip_feed`, `refill`, `cancel`, `status`, `sort_order`) 
                VALUES 
                (:cat_id, :provider_id, :p_srv_id, :name, :desc, :srv_type, 
                 :p_cost, :p_curr, :m_type, :m_val, :rate, 
                 :min_q, :max_q, :drip, :refill, :cancel, :status, :sort)";

        $this->db->execute($sql, [
            ':cat_id' => $data['category_id'],
            ':provider_id' => $data['provider_id'] ?? null,
            ':p_srv_id' => $data['provider_service_id'] ?? null,
            ':name' => $data['name'],
            ':desc' => $data['description'] ?? null,
            ':srv_type' => $data['service_type'] ?? 'default',
            ':p_cost' => $data['provider_cost'] ?? '0.00000000',
            ':p_curr' => $data['provider_currency'] ?? 'USD',
            ':m_type' => $data['margin_type'] ?? 'percentage',
            ':m_val' => $data['margin_value'] ?? '20.00000000',
            ':rate' => $data['rate'],
            ':min_q' => (int)($data['min_quantity'] ?? 10),
            ':max_q' => (int)($data['max_quantity'] ?? 10000),
            ':drip' => (int)($data['drip_feed'] ?? 0),
            ':refill' => (int)($data['refill'] ?? 0),
            ':cancel' => (int)($data['cancel'] ?? 0),
            ':status' => $data['status'] ?? 'active',
            ':sort' => (int)($data['sort_order'] ?? 0),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE `services` SET 
                `category_id` = :cat_id, `provider_id` = :provider_id, `provider_service_id` = :p_srv_id,
                `name` = :name, `description` = :desc, `service_type` = :srv_type,
                `provider_cost` = :p_cost, `provider_currency` = :p_curr,
                `margin_type` = :m_type, `margin_value` = :m_val, `rate` = :rate,
                `min_quantity` = :min_q, `max_quantity` = :max_q,
                `drip_feed` = :drip, `refill` = :refill, `cancel` = :cancel,
                `status` = :status, `sort_order` = :sort
                WHERE `id` = :id";

        return $this->db->execute($sql, [
            ':cat_id' => $data['category_id'],
            ':provider_id' => $data['provider_id'] ?? null,
            ':p_srv_id' => $data['provider_service_id'] ?? null,
            ':name' => $data['name'],
            ':desc' => $data['description'] ?? null,
            ':srv_type' => $data['service_type'] ?? 'default',
            ':p_cost' => $data['provider_cost'] ?? '0.00000000',
            ':p_curr' => $data['provider_currency'] ?? 'USD',
            ':m_type' => $data['margin_type'] ?? 'percentage',
            ':m_val' => $data['margin_value'] ?? '20.00000000',
            ':rate' => $data['rate'],
            ':min_q' => (int)($data['min_quantity'] ?? 10),
            ':max_q' => (int)($data['max_quantity'] ?? 10000),
            ':drip' => (int)($data['drip_feed'] ?? 0),
            ':refill' => (int)($data['refill'] ?? 0),
            ':cancel' => (int)($data['cancel'] ?? 0),
            ':status' => $data['status'] ?? 'active',
            ':sort' => (int)($data['sort_order'] ?? 0),
            ':id' => $id,
        ]) >= 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM `services` WHERE `id` = :id", [':id' => $id]) > 0;
    }

    public function countTotal(): int
    {
        $res = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM `services` WHERE `status` = 'active'");
        return (int)($res['cnt'] ?? 0);
    }
}
