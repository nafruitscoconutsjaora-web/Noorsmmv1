<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class CronTaskRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllTasks(): array
    {
        return $this->db->query("SELECT * FROM `cron_tasks` ORDER BY `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByKey(string $key): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `cron_tasks` WHERE `task_key` = :key");
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function toggleTask(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE `cron_tasks` SET `is_enabled` = IF(`is_enabled`=1, 0, 1), `updated_at` = NOW() WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function updateTaskRun(string $taskKey, string $status, float $duration, ?string $error = null): void
    {
        $stmt = $this->db->prepare("UPDATE `cron_tasks` 
            SET `last_run_at` = NOW(), `last_duration` = :dur, `last_status` = :status, `last_error` = :err, `is_running` = 0, `updated_at` = NOW() 
            WHERE `task_key` = :key");
        $stmt->execute([
            ':dur' => $duration,
            ':status' => $status,
            ':err' => $error,
            ':key' => $taskKey,
        ]);
    }

    public function getRecentCronLogs(int $limit = 30): array
    {
        $stmt = $this->db->prepare("SELECT * FROM `cron_logs` ORDER BY `created_at` DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
