<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class CronLogRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function logStart(string $jobName): int
    {
        $sql = "INSERT INTO `cron_logs` (`task_name`, `status`, `start_time`) VALUES (:job, 'running', NOW())";
        $this->db->execute($sql, [':job' => $jobName]);
        return (int)$this->db->lastInsertId();
    }

    public function logFinish(int $logId, string $status, ?string $output = null): void
    {
        $sql = "UPDATE `cron_logs` SET 
                `status` = :st, 
                `message` = :msg, 
                `finish_time` = NOW(),
                `duration_seconds` = TIMESTAMPDIFF(MICROSECOND, `start_time`, NOW()) / 1000000 
                WHERE `id` = :id";
        $this->db->execute($sql, [':st' => $status, ':msg' => $output, ':id' => $logId]);
    }

    public function getRecent(int $limit = 20): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `cron_logs` ORDER BY `id` DESC LIMIT {$limit}"
        );
    }
}
