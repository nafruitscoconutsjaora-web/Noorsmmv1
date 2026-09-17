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

class AdminServiceController extends BaseController
{
    private ServiceRepository $serviceRepo;
    private CategoryRepository $categoryRepo;
    private ProviderRepository $providerRepo;
    private PricingService $pricingService;
    private ProviderService $providerService;

    public function __construct()
    {
        $this->serviceRepo = new ServiceRepository();
        $this->categoryRepo = new CategoryRepository();
        $this->providerRepo = new ProviderRepository();
        $this->pricingService = new PricingService();
        $this->providerService = new ProviderService();
    }

    public function index(Request $request): Response
    {
        $page = max(1, (int)$request->query('page', 1));
        $search = trim((string)$request->query('search', ''));
        $categoryId = (int)$request->query('category_id', 0);

        $services = $this->serviceRepo->getAdminPaginated($page, 20, $search, $categoryId ?: null);
        $categories = $this->categoryRepo->getAll();

        return view('admin/services/index', [
            'services' => $services,
            'categories' => $categories,
            'search' => $search,
            'selected_category_id' => $categoryId,
        ], 'admin');
    }

    public function create(Request $request): Response
    {
        $categories = $this->categoryRepo->getAll();
        $providers = $this->providerRepo->getAllActive();

        return view('admin/services/create', [
            'categories' => $categories,
            'providers' => $providers,
        ], 'admin');
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'category_id' => 'required|integer',
            'name' => 'required|min:3|max:150',
            'rate' => 'required|numeric|min:0.01',
            'min_quantity' => 'required|integer|min:1',
            'max_quantity' => 'required|integer|min:1',
        ]);

        $serviceId = $this->serviceRepo->create([
            'category_id' => (int)$data['category_id'],
            'provider_id' => !empty($request->post('provider_id')) ? (int)$request->post('provider_id') : null,
            'provider_service_id' => $request->post('provider_service_id') ?: null,
            'name' => $data['name'],
            'description' => $request->post('description') ?: null,
            'service_type' => $request->post('service_type', 'default'),
            'provider_cost' => $request->post('provider_cost', '0.00000000'),
            'provider_currency' => $request->post('provider_currency', 'USD'),
            'margin_type' => $request->post('margin_type', 'percentage'),
            'margin_value' => $request->post('margin_value', '20.00000000'),
            'rate' => $data['rate'],
            'min_quantity' => (int)$data['min_quantity'],
            'max_quantity' => (int)$data['max_quantity'],
            'drip_feed' => $request->post('drip_feed') ? 1 : 0,
            'refill' => $request->post('refill') ? 1 : 0,
            'cancel' => $request->post('cancel') ? 1 : 0,
            'status' => $request->post('status', 'active'),
            'sort_order' => (int)$request->post('sort_order', 0),
        ]);

        Session::setFlash('success', "Service '{$data['name']}' created successfully (ID #{$serviceId}).");
        return $this->redirect('/admin/services');
    }

    public function edit(Request $request, string $id): Response
    {
        $service = $this->serviceRepo->findById((int)$id);
        if (!$service) {
            Session::setFlash('error', 'Service not found.');
            return $this->redirect('/admin/services');
        }

        $categories = $this->categoryRepo->getAll();
        $providers = $this->providerRepo->getAllActive();

        return view('admin/services/edit', [
            'service' => $service,
            'categories' => $categories,
            'providers' => $providers,
        ], 'admin');
    }

    public function update(Request $request, string $id): Response
    {
        $serviceId = (int)$id;
        $data = $this->validate($request->all(), [
            'category_id' => 'required|integer',
            'name' => 'required|min:3|max:150',
            'rate' => 'required|numeric|min:0.01',
            'min_quantity' => 'required|integer|min:1',
            'max_quantity' => 'required|integer|min:1',
        ]);

        $this->serviceRepo->update($serviceId, [
            'category_id' => (int)$data['category_id'],
            'provider_id' => !empty($request->post('provider_id')) ? (int)$request->post('provider_id') : null,
            'provider_service_id' => $request->post('provider_service_id') ?: null,
            'name' => $data['name'],
            'description' => $request->post('description') ?: null,
            'service_type' => $request->post('service_type', 'default'),
            'provider_cost' => $request->post('provider_cost', '0.00000000'),
            'provider_currency' => $request->post('provider_currency', 'USD'),
            'margin_type' => $request->post('margin_type', 'percentage'),
            'margin_value' => $request->post('margin_value', '20.00000000'),
            'rate' => $data['rate'],
            'min_quantity' => (int)$data['min_quantity'],
            'max_quantity' => (int)$data['max_quantity'],
            'drip_feed' => $request->post('drip_feed') ? 1 : 0,
            'refill' => $request->post('refill') ? 1 : 0,
            'cancel' => $request->post('cancel') ? 1 : 0,
            'status' => $request->post('status', 'active'),
            'sort_order' => (int)$request->post('sort_order', 0),
        ]);

        Session::setFlash('success', "Service #{$serviceId} updated successfully.");
        return $this->redirect('/admin/services');
    }

    public function delete(Request $request, string $id): Response
    {
        $this->serviceRepo->delete((int)$id);
        Session::setFlash('success', 'Service deleted successfully.');
        return $this->redirect('/admin/services');
    }

    // Categories management
    public function categories(Request $request): Response
    {
        $categories = $this->categoryRepo->getAll();
        return view('admin/categories/index', ['categories' => $categories], 'admin');
    }

    public function storeCategory(Request $request): Response
    {
        $data = $this->validate($request->all(), [
            'name' => 'required|min:2|max:100',
        ]);

        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name']), '-'));

        $this->categoryRepo->create([
            'name' => $data['name'],
            'slug' => $slug . '-' . time(),
            'icon' => $request->post('icon') ?: 'layers',
            'sort_order' => (int)$request->post('sort_order', 0),
            'status' => $request->post('status', 'active'),
        ]);

        Session::setFlash('success', "Category '{$data['name']}' created successfully.");
        return $this->redirect('/admin/categories');
    }

    public function deleteCategory(Request $request, string $id): Response
    {
        $this->categoryRepo->delete((int)$id);
        Session::setFlash('success', 'Category removed successfully.');
        return $this->redirect('/admin/categories');
    }
}
