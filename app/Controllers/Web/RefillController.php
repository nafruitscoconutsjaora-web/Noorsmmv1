<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\RefillRepository;
use PDO;

class RefillController extends BaseController
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
        $user = $this->user();
        $refills = $this->refillRepo->getRefillsForUser($user['id']);
        $cancellations = $this->refillRepo->getCancellationsForUser($user['id']);

        return view('user/orders/refills', [
            'refills' => $refills,
            'cancellations' => $cancellations,
        ], 'user');
    }

    public function requestRefill(Request $request, string $orderId): Response
    {
        $user = $this->user();
        $oid = (int)$orderId;

        // Verify order belongs to user and supports refill
        $sql = "SELECT o.*, s.refill as service_refill 
                FROM `orders` o 
                JOIN `services` s ON o.service_id = s.id 
                WHERE o.id = :id AND o.user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $oid, ':user_id' => $user['id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            flash('error', 'Order not found.');
            return $this->redirect('/orders');
        }

        if (!$order['service_refill']) {
            flash('error', 'This service does not support refill.');
            return $this->redirect('/orders/' . $oid . '/tracking');
        }

        // Check if there is already a pending refill
        $stmtCheck = $this->db->prepare("SELECT 1 FROM `refill_requests` WHERE `order_id` = :id AND `status` IN ('pending', 'processing')");
        $stmtCheck->execute([':id' => $oid]);
        if ($stmtCheck->fetchColumn()) {
            flash('error', 'A refill request for this order is already in progress.');
            return $this->redirect('/orders/' . $oid . '/tracking');
        }

        $this->refillRepo->createRefillRequest($oid, $user['id'], $order['provider_id'] ? (int)$order['provider_id'] : null);
        flash('success', 'Refill request submitted successfully. Provider status will update automatically.');
        return $this->redirect('/orders/' . $oid . '/tracking');
    }

    public function requestCancellation(Request $request, string $orderId): Response
    {
        $user = $this->user();
        $oid = (int)$orderId;
        $reason = (string)$request->input('reason', 'User requested cancellation');

        $sql = "SELECT o.*, s.cancel as service_cancel 
                FROM `orders` o 
                JOIN `services` s ON o.service_id = s.id 
                WHERE o.id = :id AND o.user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $oid, ':user_id' => $user['id']]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            flash('error', 'Order not found.');
            return $this->redirect('/orders');
        }

        if (!$order['service_cancel']) {
            flash('error', 'This service does not support cancellation.');
            return $this->redirect('/orders/' . $oid . '/tracking');
        }

        if (in_array($order['status'], ['completed', 'cancelled', 'refunded'])) {
            flash('error', 'Cannot request cancellation for this order status.');
            return $this->redirect('/orders/' . $oid . '/tracking');
        }

        $this->refillRepo->createCancellationRequest($oid, $user['id'], $order['provider_id'] ? (int)$order['provider_id'] : null, $reason);
        flash('success', 'Cancellation request submitted.');
        return $this->redirect('/orders/' . $oid . '/tracking');
    }
}
