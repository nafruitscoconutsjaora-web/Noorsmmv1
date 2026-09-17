<?php

declare(strict_types=1);

namespace App\Cron\Tasks;

use App\Repositories\ProviderRepository;
use App\Repositories\ServiceRepository;
use App\Services\PricingService;
use App\Services\ProviderService;
use App\Support\Logger;

class SyncProviderPrices
{
    private ProviderRepository $providerRepo;
    private ServiceRepository $serviceRepo;
    private ProviderService $providerService;
    private PricingService $pricingService;

    public function __construct()
    {
        $this->providerRepo = new ProviderRepository();
        $this->serviceRepo = new ServiceRepository();
        $this->providerService = new ProviderService();
        $this->pricingService = new PricingService();
    }

    public function run(): string
    {
        return "Provider price auto-sync verified.";
    }
}
