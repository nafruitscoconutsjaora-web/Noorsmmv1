<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthorizationException;
use PDO;

class TrackingController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function show(Request $request, string $id): Response
    {
        $user = $this->user();
        $orderId = (int)$id;

        $sql = "SELECT o.*, s.name as service_name, s.refill as service_refill, s.cancel as service_cancel, c.name as category_name 
                FROM `orders` o 
                JOIN `services` s ON o.service_id = s.id 
                JOIN `categories` c ON s.category_id = c.id 
                WHERE o.id = :id AND o.user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $orderId, ':user_id' => $user['id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new NotFoundException("Order #{$orderId} not found or does not belong to you.");
        }

        // Fetch status history timeline
        $stmtHistory = $this->db->prepare("SELECT * FROM `order_status_history` WHERE `order_id` = :id ORDER BY `created_at` ASC");
        $stmtHistory->execute([':id' => $orderId]);
        $history = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

        // Fetch refill history for this order
        $stmtRefill = $this->db->prepare("SELECT * FROM `refill_requests` WHERE `order_id` = :id ORDER BY `created_at` DESC");
        $stmtRefill->execute([':id' => $orderId]);
        $refills = $stmtRefill->fetchAll(PDO::FETCH_ASSOC);

        // Calculate progress percentage
        $progress = 0;
        if ($order['status'] === 'completed') {
            $progress = 100;
        } elseif (in_array($order['status'], ['cancelled', 'failed', 'refunded'])) {
            $progress = 0;
        } elseif ($order['quantity'] > 0 && isset($order['remains']) && $order['remains'] !== null) {
            $delivered = max(0, $order['quantity'] - $order['remains']);
            $progress = (int)min(95, max(5, round(($delivered / $order['quantity']) * 100)));
        } elseif ($order['status'] === 'in_progress') {
            $progress = 50;
        } elseif ($order['status'] === 'processing') {
            $progress = 25;
        } else {
            $progress = 10;
        }

        return view('user/orders/tracking', [
            'order' => $order,
            'history' => $history,
            'refills' => $refills,
            'progress' => $progress,
        ], 'user');
    }

    /**
     * JSON endpoint for automatic polling refresh
     */
    public function pollStatus(Request $request, string $id): Response
    {
        $user = $this->user();
        $orderId = (int)$id;

        $stmt = $this->db->prepare("SELECT id, status, start_counter, remains, quantity, updated_at FROM `orders` WHERE `id` = :id AND `user_id` = :user_id");
        $stmt->execute([':id' => $orderId, ':user_id' => $user['id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
        }

        $progress = 0;
        if ($order['status'] === 'completed') {
            $progress = 100;
        } elseif (in_array($order['status'], ['cancelled', 'failed', 'refunded'])) {
            $progress = 0;
        } elseif ($order['quantity'] > 0 && isset($order['remains']) && $order['remains'] !== null) {
            $delivered = max(0, $order['quantity'] - $order['remains']);
            $progress = (int)min(95, max(5, round(($delivered / $order['quantity']) * 100)));
        } else {
            $progress = match ($order['status']) {
                'in_progress' => 50,
                'processing' => 25,
                default => 10,
            };
        }

        return $this->json([
            'order_id' => $order['id'],
            'status' => $order['status'],
            'badge_class' => order_status_badge($order['status']),
            'start_counter' => $order['start_counter'],
            'remains' => $order['remains'],
            'progress' => $progress,
            'updated_at' => $order['updated_at'],
        ]);
    }
}
