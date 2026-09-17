<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AdminRoleRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getRoles(): array
    {
        $sql = "SELECT r.*, COUNT(a.id) as staff_count 
                FROM `roles` r 
                LEFT JOIN `admins` a ON a.role_id = r.id 
                GROUP BY r.id, r.name, r.slug, r.description";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPermissions(): array
    {
        return $this->db->query("SELECT * FROM `permissions` ORDER BY `name` ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRolePermissions(int $roleId): array
    {
        $stmt = $this->db->prepare("SELECT permission_id FROM `role_permissions` WHERE `role_id` = :role_id");
        $stmt->execute([':role_id' => $roleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function saveRolePermissions(int $roleId, array $permissionIds): void
    {
        $this->db->beginTransaction();
        try {
            $stmtDel = $this->db->prepare("DELETE FROM `role_permissions` WHERE `role_id` = :role_id");
            $stmtDel->execute([':role_id' => $roleId]);

            $stmtIns = $this->db->prepare("INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (:role_id, :perm_id)");
            foreach ($permissionIds as $permId) {
                $stmtIns->execute([':role_id' => $roleId, ':perm_id' => (int)$permId]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getStaffMembers(): array
    {
        $sql = "SELECT a.*, r.name as role_name 
                FROM `admins` a 
                JOIN `roles` r ON a.role_id = r.id 
                ORDER BY a.created_at ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStaffRole(int $adminId, int $roleId): bool
    {
        $stmt = $this->db->prepare("UPDATE `admins` SET `role_id` = :role_id WHERE `id` = :id");
        return $stmt->execute([':role_id' => $roleId, ':id' => $adminId]);
    }
}
