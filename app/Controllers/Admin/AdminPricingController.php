<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

class AdminPricingController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request): Response
    {
        $categories = $this->db->query("SELECT c.*, COUNT(s.id) as service_count, AVG(s.rate) as avg_rate 
                                        FROM `categories` c 
                                        LEFT JOIN `services` s ON s.category_id = c.id 
                                        GROUP BY c.id, c.name, c.sort_order, c.status 
                                        ORDER BY c.sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

        $globalMarkup = (float)setting('default_markup_percent', 25.0);

        return view('admin/pricing/index', [
            'categories' => $categories,
            'global_markup' => $globalMarkup,
        ], 'admin');
    }

    public function applyGlobal(Request $request): Response
    {
        $percent = max(0, min(500, (float)$request->input('percent')));

        // Save setting
        $stmt = $this->db->prepare("INSERT INTO `settings` (`key`, `value`, `updated_at`) 
            VALUES ('default_markup_percent', :val, NOW()) 
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = NOW()");
        $stmt->execute([':val' => (string)$percent]);

        // Recalculate service rates from provider_rate or base rate
        $multiplier = 1 + ($percent / 100);
        $stmtUpdate = $this->db->prepare("UPDATE `services` 
            SET `rate` = ROUND(IF(`provider_rate` > 0, `provider_rate` * :mult, `rate` * :mult), 4) 
            WHERE `status` = 'active'");
        $stmtUpdate->execute([':mult' => $multiplier]);

        flash('success', "Applied {$percent}% global markup across all active services.");
        return $this->redirect('/admin/pricing');
    }

    public function applyCategory(Request $request, string $id): Response
    {
        $categoryId = (int)$id;
        $percent = max(0, min(500, (float)$request->input('percent')));

        $multiplier = 1 + ($percent / 100);
        $stmtUpdate = $this->db->prepare("UPDATE `services` 
            SET `rate` = ROUND(IF(`provider_rate` > 0, `provider_rate` * :mult, `rate` * :mult), 4) 
            WHERE `category_id` = :cat_id");
        $stmtUpdate->execute([':mult' => $multiplier, ':cat_id' => $categoryId]);

        flash('success', "Applied {$percent}% markup to category #{$categoryId} services.");
        return $this->redirect('/admin/pricing');
    }
}
