<?php

declare(strict_types=1);

namespace App\Cron\Tasks;

use App\Repositories\ProviderRepository;
use App\Services\ProviderService;
use App\Support\Logger;

class SyncProviderServices
{
    private ProviderRepository $providerRepo;
    private ProviderService $providerService;

    public function __construct()
    {
        $this->providerRepo = new ProviderRepository();
        $this->providerService = new ProviderService();
    }

    public function run(): string
    {
        $providers = $this->providerRepo->getAllActive();
        $synced = 0;

        foreach ($providers as $p) {
            try {
                $this->providerService->testConnection((int)$p['id']);
                $synced++;
            } catch (\Throwable $e) {
                Logger::error("SyncProviderServices error on provider #{$p['id']}: " . $e->getMessage(), [], 'cron');
            }
        }

        return "Synced balances & connectivity for {$synced} active providers.";
    }
}
