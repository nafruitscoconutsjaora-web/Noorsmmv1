<?php

declare(strict_types=1);

namespace App\Cron\Tasks;

use App\Repositories\OrderRepository;
use App\Repositories\ProviderRepository;
use App\Services\OrderService;
use App\Services\ProviderService;
use App\Support\Logger;

class UpdateOrderStatuses
{
    private OrderRepository $orderRepo;
    private ProviderRepository $providerRepo;
    private ProviderService $providerService;
    private OrderService $orderService;

    public function __construct()
    {
        $this->orderRepo = new OrderRepository();
        $this->providerRepo = new ProviderRepository();
        $this->providerService = new ProviderService();
        $this->orderService = new OrderService();
    }

    public function run(): string
    {
        $orders = $this->orderRepo->getPendingProviderOrders(50);
        $updated = 0;

        foreach ($orders as $order) {
            $provider = [
                'api_url' => $order['api_url'],
                'api_key' => $order['api_key'],
            ];

            try {
                $statusRes = $this->providerService->checkOrderStatus($provider, (string)$order['provider_order_id']);
                if (!$statusRes['success']) {
                    continue;
                }

                $newStatus = $statusRes['status'];
                $startCount = $statusRes['start_count'];
                $remains = $statusRes['remains'];

                // Update start counter & remains if provided
                if ($startCount !== null || $remains !== null) {
                    $this->orderRepo->updateCounters((int)$order['id'], $startCount, $remains);
                }

                // Map standard statuses
                $mappedStatus = match ($newStatus) {
                    'completed' => 'completed',
                    'processing' => 'processing',
                    'in progress', 'inprogress' => 'in_progress',
                    'partial' => 'partial',
                    'canceled', 'cancelled' => 'cancelled',
                    'refunded' => 'refunded',
                    default => 'pending',
                };

                if ($mappedStatus === 'partial') {
                    $this->orderService->partialOrder((int)$order['id'], (int)($remains ?? 0), 'Provider marked partial');
                    $updated++;
                } elseif ($mappedStatus === 'cancelled' || $mappedStatus === 'refunded') {
                    $this->orderService->cancelOrder((int)$order['id'], 'Provider marked cancelled/refunded', 'cron:provider_sync');
                    $updated++;
                } elseif ($mappedStatus !== $order['status']) {
                    $this->orderRepo->updateStatus((int)$order['id'], $mappedStatus, 'Status updated from upstream provider', 'cron:provider_sync');
                    $updated++;
                }
            } catch (\Throwable $e) {
                Logger::error("Failed to sync status for order #{$order['id']}: " . $e->getMessage(), [], 'cron');
            }
        }

        return "Processed " . count($orders) . " orders, updated {$updated} statuses.";
    }
}
