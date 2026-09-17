<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class NotificationRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getForUser(int $userId, ?string $type = null, bool $unreadOnly = false, int $limit = 20, int $offset = 0): array
    {
        $where = "WHERE (`user_id` = :user_id OR `is_global` = 1)";
        $params = [':user_id' => $userId];

        if ($unreadOnly) {
            $where .= " AND `is_read` = 0";
        }
        if ($type && $type !== 'all') {
            $where .= " AND `type` = :type";
            $params[':type'] = $type;
        }

        $sql = "SELECT * FROM `notifications` {$where} ORDER BY `created_at` DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countForUser(int $userId, ?string $type = null, bool $unreadOnly = false): int
    {
        $where = "WHERE (`user_id` = :user_id OR `is_global` = 1)";
        $params = [':user_id' => $userId];

        if ($unreadOnly) {
            $where .= " AND `is_read` = 0";
        }
        if ($type && $type !== 'all') {
            $where .= " AND `type` = :type";
            $params[':type'] = $type;
        }

        $sql = "SELECT COUNT(*) FROM `notifications` {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE `notifications` SET `is_read` = 1 WHERE `id` = :id AND (`user_id` = :user_id OR `is_global` = 1)");
        return $stmt->execute([':id' => $notificationId, ':user_id' => $userId]);
    }

    public function markAllAsRead(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE `notifications` SET `is_read` = 1 WHERE `user_id` = :user_id OR `is_global` = 1");
        return $stmt->execute([':user_id' => $userId]);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `is_read`, `is_global`, `created_at`) 
                VALUES (:user_id, :title, :message, :type, 0, :is_global, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'] ?? null,
            ':title' => $data['title'],
            ':message' => $data['message'],
            ':type' => $data['type'] ?? 'info',
            ':is_global' => !empty($data['is_global']) ? 1 : 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getAllAdmin(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare("SELECT n.*, u.username FROM `notifications` n LEFT JOIN `users` u ON n.user_id = u.id ORDER BY n.created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
