<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class AnalyticsRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * User Order Analytics
     */
    public function getUserOrderStats(int $userId, ?string $startDate = null, ?string $endDate = null, ?int $serviceId = null, ?string $status = null): array
    {
        $params = [':user_id' => $userId];
        $where = "WHERE `user_id` = :user_id";

        if ($startDate) {
            $where .= " AND `created_at` >= :start_date";
            $params[':start_date'] = $startDate . ' 00:00:00';
        }
        if ($endDate) {
            $where .= " AND `created_at` <= :end_date";
            $params[':end_date'] = $endDate . ' 23:59:59';
        }
        if ($serviceId) {
            $where .= " AND `service_id` = :service_id";
            $params[':service_id'] = $serviceId;
        }
        if ($status && $status !== 'all') {
            $where .= " AND `status` = :status";
            $params[':status'] = $status;
        }

        // Summary counts and spending
        $sqlSummary = "SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN `status` = 'completed' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN `status` = 'pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN `status` IN ('processing', 'in_progress') THEN 1 ELSE 0 END) as processing_orders,
            SUM(CASE WHEN `status` IN ('cancelled', 'refunded', 'failed') THEN 1 ELSE 0 END) as cancelled_orders,
            COALESCE(SUM(`charge`), 0) as total_spending
            FROM `orders` {$where}";

        $stmt = $this->db->prepare($sqlSummary);
        $stmt->execute($params);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_orders' => 0,
            'completed_orders' => 0,
            'pending_orders' => 0,
            'processing_orders' => 0,
            'cancelled_orders' => 0,
            'total_spending' => '0.00000000',
        ];

        // Monthly spending (past 6 months)
        $sqlMonthly = "SELECT 
            DATE_FORMAT(`created_at`, '%Y-%m') as month_label,
            COUNT(*) as orders_count,
            COALESCE(SUM(`charge`), 0) as monthly_spent
            FROM `orders`
            WHERE `user_id` = :user_id AND `created_at` >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(`created_at`, '%Y-%m')
            ORDER BY month_label ASC";
        $stmtMonth = $this->db->prepare($sqlMonthly);
        $stmtMonth->execute([':user_id' => $userId]);
        $monthlySpending = $stmtMonth->fetchAll(PDO::FETCH_ASSOC);

        // Service-wise distribution (Top 5)
        $sqlServices = "SELECT 
            s.name as service_name,
            COUNT(o.id) as orders_count,
            COALESCE(SUM(o.charge), 0) as total_spent
            FROM `orders` o
            JOIN `services` s ON o.service_id = s.id
            {$where}
            GROUP BY o.service_id, s.name
            ORDER BY orders_count DESC
            LIMIT 6";
        $stmtServices = $this->db->prepare($sqlServices);
        $stmtServices->execute($params);
        $serviceStats = $stmtServices->fetchAll(PDO::FETCH_ASSOC);

        // Daily order counts for the last 14 days
        $sqlDaily = "SELECT 
            DATE(`created_at`) as order_date,
            COUNT(*) as count,
            COALESCE(SUM(`charge`), 0) as daily_spent
            FROM `orders`
            WHERE `user_id` = :user_id AND `created_at` >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
            GROUP BY DATE(`created_at`)
            ORDER BY order_date ASC";
        $stmtDaily = $this->db->prepare($sqlDaily);
        $stmtDaily->execute([':user_id' => $userId]);
        $dailyStats = $stmtDaily->fetchAll(PDO::FETCH_ASSOC);

        return [
            'summary' => $summary,
            'monthly' => $monthlySpending,
            'services' => $serviceStats,
            'daily' => $dailyStats,
        ];
    }

    /**
     * Admin Global Analytics
     */
    public function getAdminGlobalStats(): array
    {
        // Revenue, Provider Cost, Profit
        $sqlFinancials = "SELECT 
            COALESCE(SUM(charge), 0) as total_revenue,
            COALESCE(SUM(CASE WHEN status = 'completed' THEN charge ELSE 0 END), 0) as completed_revenue,
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN status IN ('failed', 'cancelled') THEN 1 ELSE 0 END) as failed_orders
            FROM `orders`";
        $fin = $this->db->query($sqlFinancials)->fetch(PDO::FETCH_ASSOC);

        // Active users count
        $usersCount = (int)$this->db->query("SELECT COUNT(*) FROM `users` WHERE `status` = 'active'")->fetchColumn();
        $totalBalance = (float)$this->db->query("SELECT COALESCE(SUM(`balance`), 0) FROM `users`")->fetchColumn();

        // 30 Days Trend
        $sqlTrends = "SELECT 
            DATE(`created_at`) as stat_date,
            COUNT(*) as orders_count,
            COALESCE(SUM(`charge`), 0) as revenue
            FROM `orders`
            WHERE `created_at` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(`created_at`)
            ORDER BY stat_date ASC";
        $trends = $this->db->query($sqlTrends)->fetchAll(PDO::FETCH_ASSOC);

        // Top Performing Services
        $sqlTopServices = "SELECT 
            s.id, s.name, c.name as category_name,
            COUNT(o.id) as orders_count,
            COALESCE(SUM(o.charge), 0) as total_revenue
            FROM `orders` o
            JOIN `services` s ON o.service_id = s.id
            JOIN `categories` c ON s.category_id = c.id
            GROUP BY s.id, s.name, c.name
            ORDER BY orders_count DESC
            LIMIT 5";
        $topServices = $this->db->query($sqlTopServices)->fetchAll(PDO::FETCH_ASSOC);

        // Provider Performance
        $sqlProviders = "SELECT 
            p.id, p.name, p.balance, p.status,
            COUNT(o.id) as orders_count,
            SUM(CASE WHEN o.status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN o.status IN ('failed', 'cancelled') THEN 1 ELSE 0 END) as failed_count
            FROM `providers` p
            LEFT JOIN `orders` o ON o.provider_id = p.id
            GROUP BY p.id, p.name, p.balance, p.status";
        $providers = $this->db->query($sqlProviders)->fetchAll(PDO::FETCH_ASSOC);

        return [
            'financials' => $fin,
            'users_count' => $usersCount,
            'total_user_balance' => $totalBalance,
            'trends' => $trends,
            'top_services' => $topServices,
            'providers' => $providers,
        ];
    }

    /**
     * User Behavior & Retention Analytics (Admin)
     */
    public function getUserBehaviorStats(): array
    {
        // High-Value Customers (Top spenders)
        $sqlSpenders = "SELECT 
            u.id, u.username, u.email, u.balance, u.created_at,
            COUNT(o.id) as orders_count,
            COALESCE(SUM(o.charge), 0) as total_spent
            FROM `users` u
            LEFT JOIN `orders` o ON o.user_id = u.id
            GROUP BY u.id, u.username, u.email, u.balance, u.created_at
            ORDER BY total_spent DESC
            LIMIT 10";
        $highSpenders = $this->db->query($sqlSpenders)->fetchAll(PDO::FETCH_ASSOC);

        // Customer segmentation
        $sqlSegments = "SELECT 
            CASE 
                WHEN total_spent >= 5000 THEN 'VIP / Whale (> ₹5,000)'
                WHEN total_spent >= 1000 THEN 'Regular Buyer (₹1,000 - ₹5,000)'
                WHEN total_spent > 0 THEN 'Casual Buyer (< ₹1,000)'
                ELSE 'Non-Purchasing Registered'
            END as segment,
            COUNT(*) as users_count
            FROM (
                SELECT u.id, COALESCE(SUM(o.charge), 0) as total_spent
                FROM `users` u
                LEFT JOIN `orders` o ON o.user_id = u.id
                GROUP BY u.id
            ) as user_spends
            GROUP BY segment";
        $segments = $this->db->query($sqlSegments)->fetchAll(PDO::FETCH_ASSOC);

        // Activity metrics
        $active7Days = (int)$this->db->query("SELECT COUNT(DISTINCT user_id) FROM `orders` WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
        $active30Days = (int)$this->db->query("SELECT COUNT(DISTINCT user_id) FROM `orders` WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

        return [
            'high_spenders' => $highSpenders,
            'segments' => $segments,
            'active_7_days' => $active7Days,
            'active_30_days' => $active30Days,
        ];
    }
}
