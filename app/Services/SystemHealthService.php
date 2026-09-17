<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class SystemHealthService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getSystemHealth(): array
    {
        // 1. Database Status
        $dbStart = microtime(true);
        $dbOk = false;
        $dbLatencyMs = 0;
        $dbError = null;
        try {
            $this->db->query("SELECT 1");
            $dbOk = true;
            $dbLatencyMs = round((microtime(true) - $dbStart) * 1000, 2);
        } catch (\Throwable $e) {
            $dbError = $e->getMessage();
        }

        // 2. Storage / Cache status
        $storageDir = dirname(__DIR__, 2) . '/storage';
        $storageWritable = is_writable($storageDir);
        $diskFree = disk_free_space($storageDir);
        $diskTotal = disk_total_space($storageDir);

        // 3. Provider Statuses
        $providers = $this->db->query("SELECT id, name, api_url, balance, status, last_synced_at, last_sync_status, last_sync_error FROM `providers`")->fetchAll(PDO::FETCH_ASSOC);

        // 4. Payment Gateway Status
        $razorpayEnabled = (bool)setting('razorpay_enabled', true);
        $razorpayKey = (string)setting('razorpay_key_id', '');
        $razorpayStatus = $razorpayEnabled ? (!empty($razorpayKey) ? 'Configured' : 'Missing Key ID') : 'Disabled';

        // 5. Cron Status & Failed jobs
        $lastCron = $this->db->query("SELECT * FROM `cron_logs` ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $failedJobsCount = (int)$this->db->query("SELECT COUNT(*) FROM `cron_logs` WHERE status = 'failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();

        // 6. Failed Orders Count
        $failedOrdersCount = (int)$this->db->query("SELECT COUNT(*) FROM `orders` WHERE status = 'failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();

        return [
            'database' => [
                'status' => $dbOk ? 'Healthy' : 'Degraded',
                'latency_ms' => $dbLatencyMs,
                'error' => $dbError,
            ],
            'storage' => [
                'writable' => $storageWritable,
                'free_space_mb' => round($diskFree / (1024 * 1024), 2),
                'total_space_mb' => round($diskTotal / (1024 * 1024), 2),
            ],
            'providers' => $providers,
            'gateway' => [
                'name' => 'Razorpay Payment Gateway',
                'status' => $razorpayStatus,
            ],
            'cron' => [
                'last_run' => $lastCron,
                'failed_24h' => $failedJobsCount,
            ],
            'orders' => [
                'failed_24h' => $failedOrdersCount,
            ],
            'server_environment' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'PHP CLI Server',
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time') . 's',
            ],
        ];
    }
}
