<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class SupportTicketRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function createTicket(array $data): int
    {
        $sql = "INSERT INTO `support_tickets` (`user_id`, `subject`, `category`, `priority`, `status`, `last_reply_at`) 
                VALUES (:user_id, :subject, :category, :priority, 'open', NOW())";

        $this->db->execute($sql, [
            ':user_id' => $data['user_id'],
            ':subject' => $data['subject'],
            ':category' => $data['category'] ?? 'order',
            ':priority' => $data['priority'] ?? 'medium',
        ]);

        $ticketId = (int)$this->db->lastInsertId();

        // Add first message
        $this->addMessage($ticketId, 'user', (int)$data['user_id'], $data['message']);

        return $ticketId;
    }

    public function addMessage(int $ticketId, string $senderType, int $senderId, string $message): int
    {
        $isAdmin = ($senderType === 'admin') ? 1 : 0;
        $userId = ($senderType === 'user') ? $senderId : null;
        $adminId = ($senderType === 'admin') ? $senderId : null;

        $sql = "INSERT INTO `support_messages` (`ticket_id`, `user_id`, `admin_id`, `message`, `is_admin`) 
                VALUES (:t_id, :u_id, :a_id, :msg, :is_admin)";

        $this->db->execute($sql, [
            ':t_id' => $ticketId,
            ':u_id' => $userId,
            ':a_id' => $adminId,
            ':msg' => $message,
            ':is_admin' => $isAdmin,
        ]);

        $newStatus = ($senderType === 'user') ? 'open' : 'answered';
        $this->db->execute(
            "UPDATE `support_tickets` SET `status` = :st, `last_reply_at` = NOW(), `updated_at` = NOW() WHERE `id` = :id",
            [':st' => $newStatus, ':id' => $ticketId]
        );

        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT t.*, u.username, u.email 
                FROM `support_tickets` t 
                JOIN `users` u ON t.user_id = u.id 
                WHERE t.id = :id";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }

    public function getMessages(int $ticketId): array
    {
        $sql = "SELECT m.*, 
                       CASE WHEN m.is_admin = 1 THEN a.username ELSE u.username END as sender_name,
                       CASE WHEN m.is_admin = 1 THEN 'admin' ELSE 'user' END as sender_type
                FROM `support_messages` m 
                LEFT JOIN `users` u ON m.user_id = u.id 
                LEFT JOIN `admins` a ON m.admin_id = a.id 
                WHERE m.ticket_id = :id 
                ORDER BY m.id ASC";
        return $this->db->fetchAll($sql, [':id' => $ticketId]);
    }

    public function getUserTickets(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [':user_id' => $userId];

        $countRow = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM `support_tickets` WHERE `user_id` = :user_id",
            $params
        );
        $total = (int)($countRow['total'] ?? 0);

        $sql = "SELECT * FROM `support_tickets` 
                WHERE `user_id` = :user_id 
                ORDER BY `updated_at` DESC 
                LIMIT {$perPage} OFFSET {$offset}";
        $items = $this->db->fetchAll($sql, $params);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int)ceil($total / max(1, $perPage)),
        ];
    }

    public function getAdminPaginated(int $page = 1, int $perPage = 25, string $status = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = "WHERE 1=1";

        if ($status !== '' && $status !== 'all') {
            $where .= " AND t.status = :status";
            $params[':status'] = $status;
        }

        $countRow = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM `support_tickets` t {$where}",
            $params
        );
        $total = (int)($countRow['total'] ?? 0);

        $sql = "SELECT t.*, u.username, u.email 
                FROM `support_tickets` t 
                JOIN `users` u ON t.user_id = u.id 
                {$where} 
                ORDER BY t.updated_at DESC 
                LIMIT {$perPage} OFFSET {$offset}";
        $items = $this->db->fetchAll($sql, $params);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int)ceil($total / max(1, $perPage)),
        ];
    }

    public function updateStatus(int $ticketId, string $status): bool
    {
        return $this->db->execute(
            "UPDATE `support_tickets` SET `status` = :st, `updated_at` = NOW() WHERE `id` = :id",
            [':st' => $status, ':id' => $ticketId]
        ) > 0;
    }
}
