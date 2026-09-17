<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ApiManagementRepository;

class AdminApiCenterController extends BaseController
{
    private ApiManagementRepository $apiRepo;

    public function __construct()
    {
        $this->apiRepo = new ApiManagementRepository();
    }

    public function index(Request $request): Response
    {
        $data = $this->apiRepo->getAdminGlobalStats();
        $apiUsers = $this->apiRepo->getApiUsers();

        return view('admin/api/index', [
            'stats' => $data['stats'],
            'logs' => $data['logs'],
            'api_users' => $apiUsers,
        ], 'admin');
    }
}
