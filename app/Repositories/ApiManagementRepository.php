<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class ApiManagementRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function logRequest(?int $userId, ?string $keyPreview, string $endpoint, string $method, string $ip, int $statusCode, int $durationMs): void
    {
        $stmt = $this->db->prepare("INSERT INTO `api_request_logs` 
            (`user_id`, `api_key_preview`, `endpoint`, `method`, `ip_address`, `status_code`, `response_time_ms`, `created_at`) 
            VALUES (:uid, :key, :ep, :method, :ip, :status, :duration, NOW())");
        $stmt->execute([
            ':uid' => $userId,
            ':key' => $keyPreview,
            ':ep' => $endpoint,
            ':method' => $method,
            ':ip' => $ip,
            ':status' => $statusCode,
            ':duration' => $durationMs,
        ]);
    }

    public function getUserStats(int $userId): array
    {
        $sql = "SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN status_code = 200 THEN 1 ELSE 0 END) as successful_requests,
            SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_requests,
            AVG(response_time_ms) as avg_latency
            FROM `api_request_logs` 
            WHERE `user_id` = :uid AND `created_at` >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmtLogs = $this->db->prepare("SELECT * FROM `api_request_logs` WHERE `user_id` = :uid ORDER BY `created_at` DESC LIMIT 20");
        $stmtLogs->execute([':uid' => $userId]);
        $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

        return [
            'stats' => $stats ?: ['total_requests' => 0, 'successful_requests' => 0, 'error_requests' => 0, 'avg_latency' => 0],
            'recent_logs' => $logs,
        ];
    }

    public function getAdminGlobalStats(): array
    {
        $sql = "SELECT 
            COUNT(*) as total_requests,
            SUM(CASE WHEN status_code = 200 THEN 1 ELSE 0 END) as successful_requests,
            SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_requests,
            AVG(response_time_ms) as avg_latency
            FROM `api_request_logs` 
            WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stats = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);

        $logs = $this->db->query("SELECT arl.*, u.username 
            FROM `api_request_logs` arl 
            LEFT JOIN `users` u ON arl.user_id = u.id 
            ORDER BY arl.created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

        return [
            'stats' => $stats ?: ['total_requests' => 0, 'successful_requests' => 0, 'error_requests' => 0, 'avg_latency' => 0],
            'logs' => $logs,
        ];
    }

    public function getApiUsers(): array
    {
        $sql = "SELECT u.id, u.username, u.email, u.api_key, u.balance,
                (SELECT COUNT(*) FROM `orders` WHERE user_id = u.id) as orders_count
                FROM `users` u
                WHERE u.api_key IS NOT NULL AND u.api_key != ''
                ORDER BY orders_count DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
