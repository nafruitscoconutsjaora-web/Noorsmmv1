<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\RefillRepository;
use PDO;

class AdminRefillController extends BaseController
{
    private RefillRepository $refillRepo;
    private PDO $db;

    public function __construct()
    {
        $this->refillRepo = new RefillRepository();
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request): Response
    {
        $refills = $this->refillRepo->getAllRefillsAdmin();

        $cancellations = $this->db->query("SELECT cr.*, u.username, o.link, o.quantity, o.charge, s.name as service_name, p.name as provider_name 
            FROM `cancellation_requests` cr 
            JOIN `users` u ON cr.user_id = u.id 
            JOIN `orders` o ON cr.order_id = o.id 
            JOIN `services` s ON o.service_id = s.id 
            LEFT JOIN `providers` p ON cr.provider_id = p.id 
            ORDER BY cr.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

        return view('admin/refills/index', [
            'refills' => $refills,
            'cancellations' => $cancellations,
        ], 'admin');
    }

    public function updateRefill(Request $request, string $id): Response
    {
        $refillId = (int)$id;
        $status = (string)$request->input('status'); // approved, rejected, completed
        $note = (string)$request->input('note', '');

        $this->refillRepo->updateRefillStatus($refillId, $status, null, $note ?: null);
        flash('success', "Refill #{$refillId} marked as {$status}.");
        return $this->redirect('/admin/refills');
    }

    public function updateCancellation(Request $request, string $id): Response
    {
        $cancelId = (int)$id;
        $status = (string)$request->input('status'); // approved, rejected
        $note = (string)$request->input('note', '');

        $stmt = $this->db->prepare("UPDATE `cancellation_requests` SET `status` = :status, `admin_note` = :note, `updated_at` = NOW() WHERE `id` = :id");
        $stmt->execute([':status' => $status, ':note' => $note, ':id' => $cancelId]);

        // If approved, update order status to cancelled and refund user
        if ($status === 'approved') {
            $cr = $this->db->query("SELECT * FROM `cancellation_requests` WHERE `id` = {$cancelId}")->fetch(PDO::FETCH_ASSOC);
            if ($cr) {
                $orderService = new \App\Services\OrderService();
                try {
                    $orderService->cancelOrder((int)$cr['order_id'], 'Cancellation request approved by admin');
                } catch (\Throwable $e) {
                    // Ignore if already refunded
                }
            }
        }

        flash('success', "Cancellation request #{$cancelId} marked as {$status}.");
        return $this->redirect('/admin/refills');
    }
}
