<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use PDO;

class WalletActivityController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request): Response
    {
        $user = $this->user();
        $type = $request->query('type');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $page = max(1, (int)$request->query('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = "WHERE `user_id` = :user_id";
        $params = [':user_id' => $user['id']];

        if ($type && in_array($type, ['credit', 'debit', 'refund'])) {
            $where .= " AND `type` = :type";
            $params[':type'] = $type;
        }
        if ($startDate) {
            $where .= " AND `created_at` >= :start_date";
            $params[':start_date'] = $startDate . ' 00:00:00';
        }
        if ($endDate) {
            $where .= " AND `created_at` <= :end_date";
            $params[':end_date'] = $endDate . ' 23:59:59';
        }

        // Count total for pagination
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM `wallet_transactions` {$where}");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();
        $totalPages = (int)ceil($total / $perPage);

        // Fetch paginated transactions
        $sql = "SELECT * FROM `wallet_transactions` {$where} ORDER BY `created_at` DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch payment records (Razorpay)
        $stmtPayments = $this->db->prepare("SELECT id, order_id, payment_id, amount, currency, status, created_at 
                                           FROM `payments` 
                                           WHERE `user_id` = :user_id 
                                           ORDER BY `created_at` DESC LIMIT 10");
        $stmtPayments->execute([':user_id' => $user['id']]);
        $payments = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);

        return view('user/wallet/activity', [
            'transactions' => $transactions,
            'payments' => $payments,
            'page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
            'filters' => [
                'type' => $type,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ], 'user');
    }
}
