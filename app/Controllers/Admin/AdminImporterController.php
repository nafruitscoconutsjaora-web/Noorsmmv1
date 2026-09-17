<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\ProviderService;
use PDO;

class AdminImporterController extends BaseController
{
    private ProviderService $providerService;
    private PDO $db;

    public function __construct()
    {
        $this->providerService = new ProviderService();
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request, string $providerId): Response
    {
        $pid = (int)$providerId;
        $stmt = $this->db->prepare("SELECT * FROM `providers` WHERE `id` = :id");
        $stmt->execute([':id' => $pid]);
        $provider = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$provider) {
            flash('error', 'Provider not found.');
            return $this->redirect('/admin/providers');
        }

        $services = [];
        $error = null;
        try {
            $services = $this->providerService->fetchServices($pid);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $categories = $this->db->query("SELECT id, name FROM `categories` ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

        return view('admin/providers/importer', [
            'provider' => $provider,
            'services' => $services,
            'categories' => $categories,
            'error' => $error,
        ], 'admin');
    }

    public function import(Request $request, string $providerId): Response
    {
        $pid = (int)$providerId;
        $selected = $request->input('selected_services', []);
        $markupPercent = max(0, (float)$request->input('markup_percent', 25));
        $categoryId = (int)$request->input('category_id');

        if (empty($selected) || !is_array($selected)) {
            flash('error', 'No services selected for import.');
            return $this->redirect("/admin/providers/{$pid}/importer");
        }

        $multiplier = 1 + ($markupPercent / 100);
        $importedCount = 0;

        foreach ($selected as $itemJson) {
            $item = json_decode($itemJson, true);
            if (!$item || empty($item['service'])) {
                continue;
            }

            $providerRate = (float)($item['rate'] ?? 0);
            $sellingRate = round($providerRate * $multiplier, 4);

            $stmt = $this->db->prepare("INSERT INTO `services` 
                (`category_id`, `provider_id`, `provider_service_id`, `name`, `type`, `rate`, `provider_rate`, `min_quantity`, `max_quantity`, `refill`, `cancel`, `status`, `created_at`) 
                VALUES (:cat, :pid, :psid, :name, :type, :rate, :prate, :min, :max, :refill, :cancel, 'active', NOW())
                ON DUPLICATE KEY UPDATE 
                `rate` = VALUES(`rate`),
                `provider_rate` = VALUES(`provider_rate`),
                `min_quantity` = VALUES(`min_quantity`),
                `max_quantity` = VALUES(`max_quantity`),
                `updated_at` = NOW()");

            $stmt->execute([
                ':cat' => $categoryId,
                ':pid' => $pid,
                ':psid' => (string)$item['service'],
                ':name' => (string)($item['name'] ?? "Provider Service #{$item['service']}"),
                ':type' => (string)($item['type'] ?? 'default'),
                ':rate' => $sellingRate,
                ':prate' => $providerRate,
                ':min' => (int)($item['min'] ?? 10),
                ':max' => (int)($item['max'] ?? 100000),
                ':refill' => !empty($item['refill']) ? 1 : 0,
                ':cancel' => !empty($item['cancel']) ? 1 : 0,
            ]);

            $importedCount++;
        }

        flash('success', "Successfully imported / synced {$importedCount} services with {$markupPercent}% markup.");
        return $this->redirect('/admin/services');
    }
}
