<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

class ComparisonController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request): Response
    {
        // Accept comma separated service IDs or array
        $idsParam = (string)$request->query('ids', '');
        $serviceIds = array_filter(array_map('intval', explode(',', $idsParam)));

        $comparedServices = [];
        if (!empty($serviceIds)) {
            $inClause = implode(',', array_fill(0, count($serviceIds), '?'));
            $sql = "SELECT s.*, c.name as category_name 
                    FROM `services` s 
                    JOIN `categories` c ON s.category_id = c.id 
                    WHERE s.id IN ({$inClause}) AND s.status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($serviceIds));
            $comparedServices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Available services for dropdown picker
        $allServices = $this->db->query("SELECT s.id, s.name, s.rate, c.name as category_name 
                                         FROM `services` s 
                                         JOIN `categories` c ON s.category_id = c.id 
                                         WHERE s.status = 'active' 
                                         ORDER BY c.sort_order ASC, s.name ASC")->fetchAll(PDO::FETCH_ASSOC);

        return view('user/services/compare', [
            'compared' => $comparedServices,
            'all_services' => $allServices,
            'selected_ids' => $serviceIds,
        ], 'user');
    }
}
