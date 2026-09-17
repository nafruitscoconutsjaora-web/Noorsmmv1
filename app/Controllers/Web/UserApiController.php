<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ApiManagementRepository;
use PDO;

class UserApiController extends BaseController
{
    private ApiManagementRepository $apiRepo;
    private PDO $db;

    public function __construct()
    {
        $this->apiRepo = new ApiManagementRepository();
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request): Response
    {
        $user = $this->user();
        
        // Fetch current user api key from database
        $stmt = $this->db->prepare("SELECT api_key FROM `users` WHERE `id` = :id");
        $stmt->execute([':id' => $user['id']]);
        $apiKey = $stmt->fetchColumn();

        $statsData = $this->apiRepo->getUserStats($user['id']);

        return view('user/account/api', [
            'api_key' => $apiKey,
            'stats' => $statsData['stats'],
            'logs' => $statsData['recent_logs'],
            'rate_limit' => (int)setting('rate_limit_per_minute', 60),
        ], 'user');
    }

    public function generateKey(Request $request): Response
    {
        $user = $this->user();
        $newKey = bin2hex(random_bytes(32));

        $stmt = $this->db->prepare("UPDATE `users` SET `api_key` = :key, `updated_at` = NOW() WHERE `id` = :id");
        $stmt->execute([':key' => $newKey, ':id' => $user['id']]);

        flash('success', 'New API Key generated successfully. Keep it private and secure.');
        return $this->redirect('/account/api');
    }

    public function revokeKey(Request $request): Response
    {
        $user = $this->user();

        $stmt = $this->db->prepare("UPDATE `users` SET `api_key` = NULL, `updated_at` = NOW() WHERE `id` = :id");
        $stmt->execute([':id' => $user['id']]);

        flash('success', 'Your API Key has been revoked. All external API requests with that key are now blocked.');
        return $this->redirect('/account/api');
    }
}
