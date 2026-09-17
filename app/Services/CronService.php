<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\CronTaskRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\RefillRepository;
use PDO;

class CronService
{
    private Database $db;
    private CronTaskRepository $cronRepo;
    private OrderService $orderService;
    private ProviderService $providerService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->cronRepo = new CronTaskRepository();
        $this->orderService = new OrderService();
        $this->providerService = new ProviderService();
    }

    public function runAll(): array
    {
        $syncedOrders = $this->syncPendingOrders();
        $scheduledRuns = $this->processScheduledOrders();
        $this->syncProviderBalances();
        $this->processRefills();

        return [
            'synced_orders' => $syncedOrders,
            'scheduled_runs' => $scheduledRuns,
        ];
    }

    public function syncPendingOrders(): int
    {
        $start = microtime(true);
        $pdo = $this->db->getConnection();
        $synced = 0;
        $error = null;

        try {
            $stmt = $pdo->query("SELECT id FROM `orders` WHERE `status` IN ('pending', 'processing', 'in_progress') AND `provider_order_id` IS NOT NULL LIMIT 50");
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($orders as $o) {
                try {
                    $this->orderService->syncOrder((int)$o['id']);
                    $synced++;
                } catch (\Throwable $e) {
                    // Continue with other orders
                }
            }
            $dur = round(microtime(true) - $start, 3);
            $this->cronRepo->updateTaskRun('order_sync', 'success', $dur);
            $this->logCron('order_sync', 'success', $dur, "Synced {$synced} orders");
        } catch (\Throwable $e) {
            $dur = round(microtime(true) - $start, 3);
            $this->cronRepo->updateTaskRun('order_sync', 'failed', $dur, $e->getMessage());
            $this->logCron('order_sync', 'failed', $dur, $e->getMessage());
        }

        return $synced;
    }

    public function processScheduledOrders(): int
    {
        $start = microtime(true);
        $pdo = $this->db->getConnection();
        $runs = 0;

        try {
            $stmt = $pdo->prepare("SELECT os.*, s.rate, s.min_quantity, s.max_quantity, u.balance as user_balance 
                                   FROM `order_schedules` os 
                                   JOIN `services` s ON os.service_id = s.id 
                                   JOIN `users` u ON os.user_id = u.id 
                                   WHERE os.status = 'active' AND os.next_run_at <= NOW() 
                                   LIMIT 20");
            $stmt->execute();
            $due = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $schedRepo = new ScheduleRepository();

            foreach ($due as $item) {
                $charge = bcmul((string)($item['quantity'] / 1000), (string)$item['rate'], 8);

                // Verify user has balance
                if (bccomp((string)$item['user_balance'], $charge, 8) < 0) {
                    $schedRepo->logRun((int)$item['id'], null, 'failed', $charge, 'Insufficient user wallet balance');
                    continue;
                }

                // Place order
                try {
                    $order = $this->orderService->placeOrder((int)$item['user_id'], (int)$item['service_id'], $item['link'], (int)$item['quantity']);
                    $schedRepo->logRun((int)$item['id'], (int)$order['id'], 'success', $charge, 'Order automatically generated');

                    // Increment run
                    $newCompleted = $item['runs_completed'] + 1;
                    $isFinished = $newCompleted >= $item['runs_total'];
                    $nextRun = date('Y-m-d H:i:s', strtotime("+{$item['interval_hours']} hours"));
                    $status = $isFinished ? 'completed' : 'active';

                    $stmtUp = $pdo->prepare("UPDATE `order_schedules` 
                        SET `runs_completed` = :completed, `status` = :status, `next_run_at` = :next, `last_run_at` = NOW() 
                        WHERE `id` = :id");
                    $stmtUp->execute([
                        ':completed' => $newCompleted,
                        ':status' => $status,
                        ':next' => $nextRun,
                        ':id' => $item['id'],
                    ]);

                    $runs++;
                } catch (\Throwable $e) {
                    $schedRepo->logRun((int)$item['id'], null, 'failed', $charge, $e->getMessage());
                }
            }

            $dur = round(microtime(true) - $start, 3);
            $this->cronRepo->updateTaskRun('scheduled_orders', 'success', $dur);
            $this->logCron('scheduled_orders', 'success', $dur, "Processed {$runs} scheduled orders");
        } catch (\Throwable $e) {
            $dur = round(microtime(true) - $start, 3);
            $this->cronRepo->updateTaskRun('scheduled_orders', 'failed', $dur, $e->getMessage());
            $this->logCron('scheduled_orders', 'failed', $dur, $e->getMessage());
        }

        return $runs;
    }

    public function syncProviderBalances(): void
    {
        $start = microtime(true);
        $pdo = $this->db->getConnection();

        try {
            $providers = $pdo->query("SELECT id FROM `providers` WHERE `status` = 'active'")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($providers as $p) {
                try {
                    $this->providerService->testConnection((int)$p['id']);
                } catch (\Throwable $e) {
                    // Log but don't abort
                }
            }
            $dur = round(microtime(true) - $start, 3);
            $this->cronRepo->updateTaskRun('provider_balance', 'success', $dur);
        } catch (\Throwable $e) {
            $dur = round(microtime(true) - $start, 3);
            $this->cronRepo->updateTaskRun('provider_balance', 'failed', $dur, $e->getMessage());
        }
    }

    public function processRefills(): void
    {
        $start = microtime(true);
        $pdo = $this->db->getConnection();

        try {
            $stmt = $pdo->query("SELECT rr.*, o.provider_order_id, p.api_url, p.api_key 
                                FROM `refill_requests` rr 
                                JOIN `orders` o ON rr.order_id = o.id 
                                LEFT JOIN `providers` p ON rr.provider_id = p.id 
                                WHERE rr.status = 'pending' AND o.provider_order_id IS NOT NULL 
                                LIMIT 20");
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $refillRepo = new RefillRepository();

            foreach ($requests as $r) {
                // If provider credentials exist, send refill command
                if (!empty($r['api_url']) && !empty($r['api_key'])) {
                    try {
                        $res = $this->providerService->sendRefill([
                            'api_url' => $r['api_url'],
                            'api_key' => $r['api_key'],
                        ], (string)$r['provider_order_id']);

                        if (isset($res['refill'])) {
                            $refillRepo->updateRefillStatus((int)$r['id'], 'processing', (string)$res['refill']);
                        } else {
                            $refillRepo->updateRefillStatus((int)$r['id'], 'rejected', null, $res['error'] ?? 'Provider rejected refill');
                        }
                    } catch (\Throwable $e) {
                        $refillRepo->updateRefillStatus((int)$r['id'], 'rejected', null, $e->getMessage());
                    }
                } else {
                    // Manual approval mode
                    $refillRepo->updateRefillStatus((int)$r['id'], 'approved');
                }
            }

            $dur = round(microtime(true) - $start, 3);
            $this->cronRepo->updateTaskRun('refill_monitor', 'success', $dur);
        } catch (\Throwable $e) {
            $dur = round(microtime(true) - $start, 3);
            $this->cronRepo->updateTaskRun('refill_monitor', 'failed', $dur, $e->getMessage());
        }
    }

    private function logCron(string $task, string $status, float $duration, ?string $output = null): void
    {
        $stmt = $this->db->getConnection()->prepare("INSERT INTO `cron_logs` (`task_name`, `status`, `duration_seconds`, `output`, `created_at`) 
                                                    VALUES (:task, :status, :dur, :out, NOW())");
        $stmt->execute([
            ':task' => $task,
            ':status' => $status,
            ':dur' => $duration,
            ':out' => $output,
        ]);
    }
}
