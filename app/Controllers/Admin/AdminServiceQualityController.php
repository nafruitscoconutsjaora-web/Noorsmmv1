<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

class AdminServiceQualityController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request): Response
    {
        $sql = "SELECT 
            s.id, s.name, s.rate, s.status,
            c.name as category_name,
            p.name as provider_name,
            COUNT(o.id) as total_orders,
            SUM(CASE WHEN o.status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN o.status IN ('cancelled', 'failed', 'refunded') THEN 1 ELSE 0 END) as failed_orders,
            SUM(CASE WHEN o.status IN ('pending', 'processing', 'in_progress') THEN 1 ELSE 0 END) as active_orders
            FROM `services` s
            JOIN `categories` c ON s.category_id = c.id
            LEFT JOIN `providers` p ON s.provider_id = p.id
            LEFT JOIN `orders` o ON o.service_id = s.id
            GROUP BY s.id, s.name, s.rate, s.status, c.name, p.name
            ORDER BY total_orders DESC";
        $services = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        return view('admin/services/quality', [
            'services' => $services,
        ], 'admin');
    }
}
