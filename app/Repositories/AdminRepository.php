<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class AdminRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM `admins` WHERE `id` = :id", [':id' => $id]);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->db->fetchOne("SELECT * FROM `admins` WHERE `username` = :username", [':username' => $username]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetchOne("SELECT * FROM `admins` WHERE `email` = :email", [':email' => $email]);
    }

    public function create(array $data): int
    {
        $this->db->execute(
            "INSERT INTO `admins` (`username`, `email`, `password_hash`, `role_id`, `status`) 
             VALUES (:username, :email, :password_hash, :role_id, :status)",
            [
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':password_hash' => $data['password_hash'],
                ':role_id' => $data['role_id'] ?? 1,
                ':status' => $data['status'] ?? 'active',
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function updateLastLogin(int $id): void
    {
        $this->db->execute("UPDATE `admins` SET `last_login_at` = NOW() WHERE `id` = :id", [':id' => $id]);
    }

    public function countTotal(): int
    {
        $res = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM `admins`");
        return (int)($res['cnt'] ?? 0);
    }
}
