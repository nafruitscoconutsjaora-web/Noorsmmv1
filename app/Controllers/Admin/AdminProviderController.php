<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\CategoryRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\ServiceRepository;
use App\Services\PricingService;
use App\Services\ProviderService;

class AdminProviderController extends BaseController
{
    private ProviderRepository $providerRepo;
    private ProviderService $providerService;
    private CategoryRepository $categoryRepo;
    private ServiceRepository $serviceRepo;
    private PricingService $pricingService;

    public function __construct()
    {
        $this->providerRepo = new ProviderRepository();
        $this->providerService = new ProviderService();
        $this->categoryRepo = new CategoryRepository();
        $this->serviceRepo = new ServiceRepository();
        $this->pricingService = new PricingService();
    }

    public function index(Request $request): Response
    {
        $providers = $this->providerRepo->getAll();
        return view('admin/providers/index', ['providers' => $providers], 'admin');
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'name' => 'required|min:2|max:100',
            'api_url' => 'required|url',
            'api_key' => 'required',
            'currency' => 'required',
        ]);

        $this->providerRepo->create([
            'name' => $data['name'],
            'api_url' => $data['api_url'],
            'api_key' => $data['api_key'],
            'currency' => $data['currency'],
            'status' => $request->post('status', 'active'),
        ]);

        Session::setFlash('success', "Provider '{$data['name']}' added successfully.");
        return $this->redirect('/admin/providers');
    }

    public function test(Request $request, string $id): Response
    {
        $providerId = (int)$id;
        try {
            $result = $this->providerService->testConnection($providerId);
            if ($result['success']) {
                Session::setFlash('success', "Connection successful! Provider balance: {$result['currency']} {$result['balance']}");
            } else {
                Session::setFlash('error', "Connection failed: " . ($result['error'] ?? 'Unknown response'));
            }
        } catch (\Throwable $e) {
            Session::setFlash('error', "Connection error: " . $e->getMessage());
        }

        return $this->redirect('/admin/providers');
    }

    public function import(Request $request, string $id): Response
    {
        $providerId = (int)$id;
        $provider = $this->providerRepo->findById($providerId);
        if (!$provider) {
            Session::setFlash('error', 'Provider not found.');
            return $this->redirect('/admin/providers');
        }

        try {
            $remoteServices = $this->providerService->fetchServices($providerId);
        } catch (\Throwable $e) {
            Session::setFlash('error', "Could not fetch services: " . $e->getMessage());
            return $this->redirect('/admin/providers');
        }

        $categories = $this->categoryRepo->getAll();

        return view('admin/providers/import', [
            'provider' => $provider,
            'remote_services' => $remoteServices,
            'categories' => $categories,
        ], 'admin');
    }

    public function executeImport(Request $request, string $id): Response
    {
        $providerId = (int)$id;
        $provider = $this->providerRepo->findById($providerId);
        if (!$provider) {
            Session::setFlash('error', 'Provider not found.');
            return $this->redirect('/admin/providers');
        }

        $selectedServices = $request->post('services', []);
        $marginPercent = (float)$request->post('margin_percentage', 20.0);
        $defaultCategoryId = (int)$request->post('default_category_id', 1);

        if (empty($selectedServices) || !is_array($selectedServices)) {
            Session::setFlash('error', 'No services selected for import.');
            return $this->redirect("/admin/providers/{$providerId}/import");
        }

        $remoteServices = $this->providerService->fetchServices($providerId);
        $remoteMap = [];
        foreach ($remoteServices as $s) {
            $remoteMap[(string)$s['service']] = $s;
        }

        $importedCount = 0;
        foreach ($selectedServices as $remoteId) {
            if (!isset($remoteMap[$remoteId])) {
                continue;
            }

            $raw = $remoteMap[$remoteId];
            $cost = (string)($raw['rate'] ?? '0.00000000');
            $currency = $provider['currency'] ?? 'USD';

            $sellingRate = $this->pricingService->calculateRate(
                $cost,
                $currency,
                'INR',
                'percentage',
                (string)$marginPercent
            );

            // Find or create category
            $catName = trim($raw['category'] ?? 'General');
            $cat = $this->categoryRepo->findBySlug(strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $catName)));
            $catId = $cat ? (int)$cat['id'] : $defaultCategoryId;

            $this->serviceRepo->create([
                'category_id' => $catId,
                'provider_id' => $providerId,
                'provider_service_id' => (string)$remoteId,
                'name' => $raw['name'] ?? 'Imported Service',
                'description' => $raw['desc'] ?? null,
                'service_type' => $raw['type'] ?? 'default',
                'provider_cost' => $cost,
                'provider_currency' => $currency,
                'margin_type' => 'percentage',
                'margin_value' => (string)$marginPercent,
                'rate' => $sellingRate,
                'min_quantity' => (int)($raw['min'] ?? 10),
                'max_quantity' => (int)($raw['max'] ?? 10000),
                'drip_feed' => !empty($raw['dripfeed']) ? 1 : 0,
                'refill' => !empty($raw['refill']) ? 1 : 0,
                'cancel' => !empty($raw['cancel']) ? 1 : 0,
                'status' => 'active',
                'sort_order' => 0,
            ]);

            $importedCount++;
        }

        Session::setFlash('success', "Successfully imported {$importedCount} services from {$provider['name']} with {$marginPercent}% margin.");
        return $this->redirect('/admin/services');
    }
}
