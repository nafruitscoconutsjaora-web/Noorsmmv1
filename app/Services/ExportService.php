<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Response;
use PDO;

class ExportService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function exportUserOrdersCsv(int $userId, ?string $startDate = null, ?string $endDate = null): Response
    {
        $where = "WHERE o.user_id = :user_id";
        $params = [':user_id' => $userId];

        if ($startDate) {
            $where .= " AND o.created_at >= :start";
            $params[':start'] = $startDate . ' 00:00:00';
        }
        if ($endDate) {
            $where .= " AND o.created_at <= :end";
            $params[':end'] = $endDate . ' 23:59:59';
        }

        $sql = "SELECT o.id, s.name as service_name, o.link, o.quantity, o.charge, o.start_counter, o.remains, o.status, o.created_at 
                FROM `orders` o 
                JOIN `services` s ON o.service_id = s.id 
                {$where} 
                ORDER BY o.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $filename = "orders_export_" . date('Y-m-d_His') . ".csv";
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['Order ID', 'Service Name', 'Target Link', 'Quantity', 'Charge (INR)', 'Start Counter', 'Remains', 'Status', 'Date Placed']);

        foreach ($orders as $row) {
            fputcsv($output, [
                $row['id'],
                $row['service_name'],
                $row['link'],
                $row['quantity'],
                number_format((float)$row['charge'], 2, '.', ''),
                $row['start_counter'] ?? 'N/A',
                $row['remains'] ?? 'N/A',
                strtoupper($row['status']),
                $row['created_at'],
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        $response = new Response($csvContent);
        $response->setHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response;
    }

    public function exportUserWalletCsv(int $userId): Response
    {
        $sql = "SELECT id, type, amount, balance_before, balance_after, description, reference_id, status, created_at 
                FROM `wallet_transactions` 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $filename = "wallet_statement_" . date('Y-m-d_His') . ".csv";
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['Tx ID', 'Type', 'Amount (INR)', 'Balance Before', 'Balance After', 'Description', 'Reference ID', 'Status', 'Date']);

        foreach ($transactions as $row) {
            fputcsv($output, [
                $row['id'],
                strtoupper($row['type']),
                number_format((float)$row['amount'], 2, '.', ''),
                number_format((float)$row['balance_before'], 2, '.', ''),
                number_format((float)$row['balance_after'], 2, '.', ''),
                $row['description'],
                $row['reference_id'] ?? 'N/A',
                strtoupper($row['status']),
                $row['created_at'],
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        $response = new Response($csvContent);
        $response->setHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response;
    }

    public function exportAdminReportCsv(string $type): Response
    {
        $filename = "admin_{$type}_report_" . date('Y-m-d_His') . ".csv";
        $output = fopen('php://temp', 'r+');

        if ($type === 'orders') {
            fputcsv($output, ['Order ID', 'Username', 'Service', 'Provider', 'Provider Order ID', 'Link', 'Qty', 'Charge', 'Status', 'Date']);
            $sql = "SELECT o.id, u.username, s.name as service_name, p.name as provider_name, o.provider_order_id, o.link, o.quantity, o.charge, o.status, o.created_at 
                    FROM `orders` o 
                    JOIN `users` u ON o.user_id = u.id 
                    JOIN `services` s ON o.service_id = s.id 
                    LEFT JOIN `providers` p ON o.provider_id = p.id 
                    ORDER BY o.created_at DESC LIMIT 5000";
            $stmt = $this->db->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $row);
            }
        } elseif ($type === 'users') {
            fputcsv($output, ['User ID', 'Username', 'Email', 'Balance', 'Status', 'Registered At']);
            $sql = "SELECT id, username, email, balance, status, created_at FROM `users` ORDER BY id DESC";
            $stmt = $this->db->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $row);
            }
        } else {
            fputcsv($output, ['Payment ID', 'Username', 'Gateway', 'Order ID', 'Amount', 'Currency', 'Status', 'Date']);
            $sql = "SELECT p.id, u.username, p.gateway, p.order_id, p.amount, p.currency, p.status, p.created_at 
                    FROM `payments` p 
                    JOIN `users` u ON p.user_id = u.id 
                    ORDER BY p.created_at DESC LIMIT 5000";
            $stmt = $this->db->query($sql);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $row);
            }
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        $response = new Response($csvContent);
        $response->setHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response;
    }
}
