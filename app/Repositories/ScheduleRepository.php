<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class ScheduleRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getForUser(int $userId): array
    {
        $sql = "SELECT os.*, s.name as service_name, s.rate as service_rate, c.name as category_name 
                FROM `order_schedules` os
                JOIN `services` s ON os.service_id = s.id
                JOIN `categories` c ON s.category_id = c.id
                WHERE os.user_id = :user_id
                ORDER BY os.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByIdAndUser(int $id, int $userId): ?array
    {
        $sql = "SELECT os.*, s.name as service_name, s.rate as service_rate
                FROM `order_schedules` os
                JOIN `services` s ON os.service_id = s.id
                WHERE os.id = :id AND os.user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO `order_schedules` 
                (`user_id`, `service_id`, `link`, `quantity`, `schedule_type`, `runs_total`, `runs_completed`, `interval_hours`, `next_run_at`, `status`, `created_at`)
                VALUES (:user_id, :service_id, :link, :quantity, :schedule_type, :runs_total, 0, :interval_hours, :next_run_at, 'active', NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':service_id' => $data['service_id'],
            ':link' => $data['link'],
            ':quantity' => $data['quantity'],
            ':schedule_type' => $data['schedule_type'] ?? 'recurring',
            ':runs_total' => $data['runs_total'] ?? 1,
            ':interval_hours' => $data['interval_hours'] ?? 24,
            ':next_run_at' => $data['next_run_at'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE `order_schedules` SET `status` = :status, `updated_at` = NOW() WHERE `id` = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function getRuns(int $scheduleId): array
    {
        $sql = "SELECT osr.*, o.status as order_status 
                FROM `order_schedule_runs` osr
                LEFT JOIN `orders` o ON osr.order_id = o.id
                WHERE osr.schedule_id = :schedule_id
                ORDER BY osr.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':schedule_id' => $scheduleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function logRun(int $scheduleId, ?int $orderId, string $status, string $charge, ?string $message): void
    {
        $stmt = $this->db->prepare("INSERT INTO `order_schedule_runs` (`schedule_id`, `order_id`, `status`, `charge`, `message`, `created_at`) 
                                   VALUES (:schedule_id, :order_id, :status, :charge, :message, NOW())");
        $stmt->execute([
            ':schedule_id' => $scheduleId,
            ':order_id' => $orderId,
            ':status' => $status,
            ':charge' => $charge,
            ':message' => $message,
        ]);
    }

    public function getAllAdmin(): array
    {
        $sql = "SELECT os.*, u.username, s.name as service_name
                FROM `order_schedules` os
                JOIN `users` u ON os.user_id = u.id
                JOIN `services` s ON os.service_id = s.id
                ORDER BY os.created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
