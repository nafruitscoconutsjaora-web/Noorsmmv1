<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AuditLogRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProviderRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\UserRepository;

class AdminDashboardController extends BaseController
{
    private OrderRepository $orderRepo;
    private UserRepository $userRepo;
    private ServiceRepository $serviceRepo;
    private ProviderRepository $providerRepo;
    private AuditLogRepository $auditRepo;

    public function __construct()
    {
        $this->orderRepo = new OrderRepository();
        $this->userRepo = new UserRepository();
        $this->serviceRepo = new ServiceRepository();
        $this->providerRepo = new ProviderRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    public function index(Request $request): Response
    {
        $stats = $this->orderRepo->getGlobalStats();
        $totalUsers = $this->userRepo->countTotal();
        $totalServices = $this->serviceRepo->countTotal();
        $providers = $this->providerRepo->getAll();

        $recentOrders = $this->orderRepo->getAdminPaginated(1, 8);
        $recentAudit = $this->auditRepo->getPaginated(1, 6);

        return view('admin/dashboard/index', [
            'stats' => $stats,
            'total_users' => $totalUsers,
            'total_services' => $totalServices,
            'providers' => $providers,
            'recent_orders' => $recentOrders['items'] ?? [],
            'recent_audit' => $recentAudit['items'] ?? [],
        ], 'admin');
    }
}
