<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class FavoriteRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getFavorites(int $userId): array
    {
        $sql = "SELECT s.*, c.name as category_name, c.id as category_id 
                FROM `favorite_services` fs
                JOIN `services` s ON fs.service_id = s.id
                JOIN `categories` c ON s.category_id = c.id
                WHERE fs.user_id = :user_id AND s.status = 'active'
                ORDER BY fs.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isFavorite(int $userId, int $serviceId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM `favorite_services` WHERE `user_id` = :uid AND `service_id` = :sid");
        $stmt->execute([':uid' => $userId, ':sid' => $serviceId]);
        return (bool)$stmt->fetchColumn();
    }

    public function toggle(int $userId, int $serviceId): bool
    {
        if ($this->isFavorite($userId, $serviceId)) {
            $stmt = $this->db->prepare("DELETE FROM `favorite_services` WHERE `user_id` = :uid AND `service_id` = :sid");
            $stmt->execute([':uid' => $userId, ':sid' => $serviceId]);
            return false; // Removed
        }

        $stmt = $this->db->prepare("INSERT IGNORE INTO `favorite_services` (`user_id`, `service_id`, `created_at`) VALUES (:uid, :sid, NOW())");
        $stmt->execute([':uid' => $userId, ':sid' => $serviceId]);
        return true; // Added
    }
}
