<?php

declare(strict_types=1);

return [
    'lock_path' => dirname(__DIR__) . '/storage/temp/cron.lock',
    'max_execution_time' => 300,
    'tasks' => [
        'sync_provider_services' => \App\Cron\Tasks\SyncProviderServices::class,
        'sync_provider_prices' => \App\Cron\Tasks\SyncProviderPrices::class,
        'update_order_statuses' => \App\Cron\Tasks\UpdateOrderStatuses::class,
        'payment_reconciliation' => \App\Cron\Tasks\PaymentReconciliation::class,
        'auto_refill' => \App\Cron\Tasks\AutoRefill::class,
        'retry_failed_orders' => \App\Cron\Tasks\RetryFailedOrders::class,
        'cleanup' => \App\Cron\Tasks\Cleanup::class,
    ],
];
