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
        $fin = $stats['financials'] ?? [];
        $totalOrders = (int)($fin['total_orders'] ?? 0);
        $completedOrders = (int)($fin['completed_orders'] ?? 0);
        $totalRevenue = (float)($fin['total_revenue'] ?? 0);

        $metrics = [
            'total_revenue' => $totalRevenue,
            'estimated_profit' => round($totalRevenue * 0.25, 2),
            'total_orders' => $totalOrders,
            'completion_rate' => $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0,
        ];

        return view('admin/analytics/index', [
            'stats' => $stats,
            'metrics' => $metrics,
        ], 'admin');
    }

    public function users(Request $request): Response
    {
        $behavior = $this->analytics->getUserBehaviorStats();
        $topUsers = $behavior['high_spenders'] ?? [];
        $payingUsers = array_filter($topUsers, fn($u) => (float)($u['total_spent'] ?? 0) > 0);
        $totalPaying = count($payingUsers);
        $totalSpend = array_sum(array_column($payingUsers, 'total_spent'));
        $avgClv = $totalPaying > 0 ? ($totalSpend / $totalPaying) : 0;
        $repeatUsers = count(array_filter($topUsers, fn($u) => (int)($u['orders_count'] ?? 0) >= 2));
        $repeatRate = $totalPaying > 0 ? ($repeatUsers / $totalPaying * 100) : 0;

        $cohorts = [
            'paying_users_count' => $totalPaying,
            'avg_clv' => $avgClv,
            'repeat_buyer_rate' => $repeatRate,
        ];

        return view('admin/analytics/users', [
            'behavior' => $behavior,
            'cohorts' => $cohorts,
            'top_users' => $topUsers,
        ], 'admin');
    }
}
