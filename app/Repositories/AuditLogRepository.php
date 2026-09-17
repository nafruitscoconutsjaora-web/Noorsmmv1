<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class AuditLogRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function log(int $adminId, string $action, string $targetType, ?int $targetId = null, ?array $details = null, ?string $ip = null): void
    {
        $sql = "INSERT INTO `admin_audit_logs` 
                (`admin_id`, `action`, `resource`, `resource_id`, `metadata`, `ip_address`) 
                VALUES 
                (:admin_id, :action, :target_type, :target_id, :details, :ip)";

        $this->db->execute($sql, [
            ':admin_id' => $adminId,
            ':action' => $action,
            ':target_type' => $targetType,
            ':target_id' => $targetId ? (string)$targetId : null,
            ':details' => $details ? json_encode($details) : null,
            ':ip' => $ip,
        ]);
    }

    public function getPaginated(int $page = 1, int $perPage = 25): array
    {
        $offset = ($page - 1) * $perPage;
        $countRow = $this->db->fetchOne("SELECT COUNT(*) as total FROM `admin_audit_logs`");
        $total = (int)($countRow['total'] ?? 0);

        $sql = "SELECT l.*, a.username as admin_username 
                FROM `admin_audit_logs` l 
                JOIN `admins` a ON l.admin_id = a.id 
                ORDER BY l.id DESC 
                LIMIT {$perPage} OFFSET {$offset}";
        $items = $this->db->fetchAll($sql);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int)ceil($total / max(1, $perPage)),
        ];
    }
}
