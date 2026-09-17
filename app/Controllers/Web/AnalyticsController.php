<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\AnalyticsRepository;
use PDO;

class AnalyticsController extends BaseController
{
    private AnalyticsRepository $analytics;
    private PDO $db;

    public function __construct()
    {
        $this->analytics = new AnalyticsRepository();
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request): Response
    {
        $user = $this->user();
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $serviceId = $request->query('service_id') ? (int)$request->query('service_id') : null;
        $status = $request->query('status');

        $data = $this->analytics->getUserOrderStats($user['id'], $startDate, $endDate, $serviceId, $status);

        // Fetch user services for filter dropdown
        $stmtServices = $this->db->prepare("SELECT DISTINCT s.id, s.name 
                                           FROM `orders` o 
                                           JOIN `services` s ON o.service_id = s.id 
                                           WHERE o.user_id = :user_id ORDER BY s.name ASC");
        $stmtServices->execute([':user_id' => $user['id']]);
        $services = $stmtServices->fetchAll(PDO::FETCH_ASSOC);

        return view('user/analytics/index', [
            'summary' => $data['summary'],
            'monthly' => $data['monthly'],
            'services_stats' => $data['services'],
            'daily' => $data['daily'],
            'user_services' => $services,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'service_id' => $serviceId,
                'status' => $status,
            ],
        ], 'user');
    }
}
