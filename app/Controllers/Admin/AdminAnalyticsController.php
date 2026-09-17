<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AnalyticsRepository;

class AdminAnalyticsController extends BaseController
{
    private AnalyticsRepository $analytics;

    public function __construct()
    {
        $this->analytics = new AnalyticsRepository();
    }

    public function index(Request $request): Response
    {
        $stats = $this->analytics->getAdminGlobalStats();

        return view('admin/analytics/index', [
            'stats' => $stats,
        ], 'admin');
    }

    public function users(Request $request): Response
    {
        $behavior = $this->analytics->getUserBehaviorStats();

        return view('admin/analytics/users', [
            'behavior' => $behavior,
        ], 'admin');
    }
}
