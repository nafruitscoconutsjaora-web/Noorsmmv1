<?php

declare(strict_types=1);

namespace App\Cron\Tasks;

use App\Repositories\OrderRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\ServiceRepository;
use App\Services\ProviderService;
use App\Support\Logger;

class RetryFailedOrders
{
    public function run(): string
    {
        $orderRepo = new OrderRepository();
        $providerRepo = new ProviderRepository();
        $serviceRepo = new ServiceRepository();
        $providerService = new ProviderService();

        $unsentOrders = $orderRepo->getUnsentProviderOrders(15);
        $retried = 0;

        foreach ($unsentOrders as $order) {
            $provider = $providerRepo->findById((int)$order['provider_id']);
            $service = $serviceRepo->findById((int)$order['service_id']);

            if (!$provider || !$service) {
                continue;
            }

            $res = $providerService->sendOrder($provider, $order, $service);
            if ($res['success']) {
                $orderRepo->updateProviderInfo((int)$order['id'], $res['provider_order_id'], $res['response']);
                $orderRepo->updateStatus((int)$order['id'], 'in_progress', 'Retried provider dispatch by cron');
                $retried++;
            }
        }

        return "Processed " . count($unsentOrders) . " unsent orders, successfully dispatched {$retried}.";
    }
}
