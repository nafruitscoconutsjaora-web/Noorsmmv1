<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CategoryRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\UserRepository;

class HomeController extends BaseController
{
    private CategoryRepository $categoryRepo;
    private ServiceRepository $serviceRepo;
    private OrderRepository $orderRepo;
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->categoryRepo = new CategoryRepository();
        $this->serviceRepo = new ServiceRepository();
        $this->orderRepo = new OrderRepository();
        $this->userRepo = new UserRepository();
    }

    public function index(Request $request): Response
    {
        $categories = $this->categoryRepo->getAllActive();
        $services = $this->serviceRepo->getAllActiveWithCategory();
        $globalStats = $this->orderRepo->getGlobalStats();
        $totalUsers = $this->userRepo->countTotal();
        $totalServices = $this->serviceRepo->countTotal();

        return view('landing/index', [
            'categories' => $categories,
            'services' => $services,
            'total_orders' => $globalStats['total_orders'] ?? 0,
            'total_users' => $totalUsers,
            'total_services' => $totalServices,
        ], 'main');
    }

    public function services(Request $request): Response
    {
        $categories = $this->categoryRepo->getAllActive();
        $services = $this->serviceRepo->getAllActiveWithCategory();

        return view('landing/services', [
            'categories' => $categories,
            'services' => $services,
        ], 'main');
    }

    public function apiDocs(Request $request): Response
    {
        return view('landing/api', [], 'main');
    }

    public function terms(Request $request): Response
    {
        return view('landing/terms', [], 'main');
    }
}
