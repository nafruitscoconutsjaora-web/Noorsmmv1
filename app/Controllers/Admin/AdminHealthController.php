<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Services\SystemHealthService;

class AdminHealthController extends BaseController
{
    private SystemHealthService $healthService;

    public function __construct()
    {
        $this->healthService = new SystemHealthService();
    }

    public function index(Request $request): Response
    {
        $health = $this->healthService->getSystemHealth();

        return view('admin/system/health', [
            'health' => $health,
            'providers' => $health['providers'] ?? [],
        ], 'admin');
    }
}
