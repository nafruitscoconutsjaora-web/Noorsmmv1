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
use App\Services\AuthService;

class DashboardController extends BaseController
{
    private OrderRepository $orderRepo;
    private CategoryRepository $categoryRepo;
    private ServiceRepository $serviceRepo;
    private UserRepository $userRepo;
    private AuthService $authService;

    public function __construct()
    {
        $this->orderRepo = new OrderRepository();
        $this->categoryRepo = new CategoryRepository();
        $this->serviceRepo = new ServiceRepository();
        $this->userRepo = new UserRepository();
        $this->authService = new AuthService();
    }

    public function index(Request $request): Response
    {
        $user = $this->user();
        $this->authService->refreshUserSession((int)$user['id']);
        $freshUser = $this->userRepo->findById((int)$user['id']);

        $stats = $this->orderRepo->getUserStats((int)$user['id']);
        $recentOrders = $this->orderRepo->getUserOrders((int)$user['id'], 1, 5);
        $categories = $this->categoryRepo->getAllActive();
        $services = $this->serviceRepo->getAllActiveWithCategory();

        return view('user/dashboard', [
            'user' => $freshUser,
            'stats' => $stats,
            'recent_orders' => $recentOrders['items'] ?? [],
            'categories' => $categories,
            'services' => $services,
        ], 'user');
    }
}
