<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class RefillRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getRefillsForUser(int $userId): array
    {
        $sql = "SELECT rr.*, o.service_id, o.link, o.quantity, s.name as service_name
                FROM `refill_requests` rr
                JOIN `orders` o ON rr.order_id = o.id
                JOIN `services` s ON o.service_id = s.id
                WHERE rr.user_id = :user_id
                ORDER BY rr.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCancellationsForUser(int $userId): array
    {
        $sql = "SELECT cr.*, o.service_id, o.link, o.quantity, o.charge, s.name as service_name
                FROM `cancellation_requests` cr
                JOIN `orders` o ON cr.order_id = o.id
                JOIN `services` s ON o.service_id = s.id
                WHERE cr.user_id = :user_id
                ORDER BY cr.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createRefillRequest(int $orderId, int $userId, ?int $providerId = null): int
    {
        $stmt = $this->db->prepare("INSERT INTO `refill_requests` (`order_id`, `user_id`, `provider_id`, `status`, `created_at`) 
                                   VALUES (:order_id, :user_id, :provider_id, 'pending', NOW())");
        $stmt->execute([
            ':order_id' => $orderId,
            ':user_id' => $userId,
            ':provider_id' => $providerId,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function createCancellationRequest(int $orderId, int $userId, ?int $providerId = null, ?string $reason = null): int
    {
        $stmt = $this->db->prepare("INSERT INTO `cancellation_requests` (`order_id`, `user_id`, `provider_id`, `reason`, `status`, `created_at`) 
                                   VALUES (:order_id, :user_id, :provider_id, :reason, 'pending', NOW())");
        $stmt->execute([
            ':order_id' => $orderId,
            ':user_id' => $userId,
            ':provider_id' => $providerId,
            ':reason' => $reason,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getAllRefillsAdmin(): array
    {
        $sql = "SELECT rr.*, u.username, o.link, o.quantity, s.name as service_name, p.name as provider_name
                FROM `refill_requests` rr
                JOIN `users` u ON rr.user_id = u.id
                JOIN `orders` o ON rr.order_id = o.id
                JOIN `services` s ON o.service_id = s.id
                LEFT JOIN `providers` p ON rr.provider_id = p.id
                ORDER BY rr.created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateRefillStatus(int $id, string $status, ?string $providerRefillId = null, ?string $error = null): bool
    {
        $stmt = $this->db->prepare("UPDATE `refill_requests` 
                                   SET `status` = :status, `provider_refill_id` = COALESCE(:refill_id, `provider_refill_id`), `error_message` = :error, `updated_at` = NOW() 
                                   WHERE `id` = :id");
        return $stmt->execute([
            ':status' => $status,
            ':refill_id' => $providerRefillId,
            ':error' => $error,
            ':id' => $id,
        ]);
    }
}
