<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class UserRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM `users` WHERE `id` = :id", [':id' => $id]);
    }

    public function findByIdForUpdate(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM `users` WHERE `id` = :id FOR UPDATE", [':id' => $id]);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->db->fetchOne("SELECT * FROM `users` WHERE `username` = :username", [':username' => $username]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetchOne("SELECT * FROM `users` WHERE `email` = :email", [':email' => $email]);
    }

    public function findByApiKey(string $apiKey): ?array
    {
        return $this->db->fetchOne("SELECT * FROM `users` WHERE `api_key` = :api_key", [':api_key' => $apiKey]);
    }

    public function create(array $data): int
    {
        $apiKey = bin2hex(random_bytes(24));
        $this->db->execute(
            "INSERT INTO `users` (`username`, `email`, `password_hash`, `balance`, `status`, `api_key`, `timezone`) 
             VALUES (:username, :email, :password_hash, :balance, :status, :api_key, :timezone)",
            [
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':password_hash' => $data['password_hash'],
                ':balance' => $data['balance'] ?? '0.00000000',
                ':status' => $data['status'] ?? 'active',
                ':api_key' => $apiKey,
                ':timezone' => $data['timezone'] ?? 'UTC',
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function updateBalance(int $userId, string $newBalance): bool
    {
        return $this->db->execute(
            "UPDATE `users` SET `balance` = :balance WHERE `id` = :id",
            [':balance' => $newBalance, ':id' => $userId]
        ) > 0;
    }

    public function updateStatus(int $userId, string $status): bool
    {
        return $this->db->execute(
            "UPDATE `users` SET `status` = :status WHERE `id` = :id",
            [':status' => $status, ':id' => $userId]
        ) > 0;
    }

    public function updatePassword(int $userId, string $passwordHash): bool
    {
        return $this->db->execute(
            "UPDATE `users` SET `password_hash` = :hash WHERE `id` = :id",
            [':hash' => $passwordHash, ':id' => $userId]
        ) > 0;
    }

    public function updateApiKey(int $userId, string $newApiKey): bool
    {
        return $this->db->execute(
            "UPDATE `users` SET `api_key` = :key WHERE `id` = :id",
            [':key' => $newApiKey, ':id' => $userId]
        ) > 0;
    }

    public function getPaginated(int $page = 1, int $perPage = 20, string $search = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = "WHERE 1=1";

        if ($search !== '') {
            $where .= " AND (`username` LIKE :s1 OR `email` LIKE :s2)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
        }

        $countRow = $this->db->fetchOne("SELECT COUNT(*) as total FROM `users` {$where}", $params);
        $total = (int)($countRow['total'] ?? 0);

        $sql = "SELECT `id`, `username`, `email`, `balance`, `status`, `timezone`, `created_at` 
                FROM `users` {$where} ORDER BY `id` DESC LIMIT {$perPage} OFFSET {$offset}";
        $items = $this->db->fetchAll($sql, $params);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int)ceil($total / max(1, $perPage)),
        ];
    }

    public function countTotal(): int
    {
        $res = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM `users`");
        return (int)($res['cnt'] ?? 0);
    }
}
