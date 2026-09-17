<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\SecurityRepository;
use PDO;

class AdminFraudController extends BaseController
{
    private SecurityRepository $security;
    private PDO $db;

    public function __construct()
    {
        $this->security = new SecurityRepository();
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request): Response
    {
        // 1. Multi-account IP detection
        $multiAccounts = $this->security->getMultiAccountIndicators();

        // 2. High order velocity users (placed > 10 orders in last 1 hour)
        $sqlVelocity = "SELECT 
            u.id, u.username, u.email, u.balance,
            COUNT(o.id) as orders_last_hour
            FROM `orders` o 
            JOIN `users` u ON o.user_id = u.id 
            WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) 
            GROUP BY u.id, u.username, u.email, u.balance 
            HAVING orders_last_hour >= 5 
            ORDER BY orders_last_hour DESC";
        $velocityAlerts = $this->db->query($sqlVelocity)->fetchAll(PDO::FETCH_ASSOC);

        // 3. Security events
        $securityEvents = $this->security->getSecurityEventsAdmin(30);

        $flaggedItems = array_map(function ($ev) {
            return [
                'id' => $ev['id'],
                'created_at' => $ev['created_at'],
                'user_id' => $ev['user_id'] ?? 0,
                'username' => $ev['user_name'] ?? 'System',
                'rule_triggered' => $ev['event_type'] ?? 'Security Event',
                'severity' => $ev['severity'] ?? 'medium',
                'metadata' => $ev['details'] ?? $ev['ip_address'] ?? 'N/A',
            ];
        }, $securityEvents);

        return view('admin/fraud/index', [
            'multi_accounts' => $multiAccounts,
            'velocity_alerts' => $velocityAlerts,
            'security_events' => $securityEvents,
            'flagged_items' => $flaggedItems,
        ], 'admin');
    }
}
