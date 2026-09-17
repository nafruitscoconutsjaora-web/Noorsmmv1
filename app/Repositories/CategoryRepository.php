<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class CategoryRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function getAllActive(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM `categories` WHERE `status` = 'active' ORDER BY `sort_order` ASC, `name` ASC"
        );
    }

    public function getAll(): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, (SELECT COUNT(*) FROM `services` s WHERE s.category_id = c.id) as service_count 
             FROM `categories` c ORDER BY `sort_order` ASC, `id` ASC"
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM `categories` WHERE `id` = :id", [':id' => $id]);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->db->fetchOne("SELECT * FROM `categories` WHERE `slug` = :slug", [':slug' => $slug]);
    }

    public function create(array $data): int
    {
        $this->db->execute(
            "INSERT INTO `categories` (`name`, `slug`, `icon`, `sort_order`, `status`) 
             VALUES (:name, :slug, :icon, :sort_order, :status)",
            [
                ':name' => $data['name'],
                ':slug' => $data['slug'],
                ':icon' => $data['icon'] ?? null,
                ':sort_order' => (int)($data['sort_order'] ?? 0),
                ':status' => $data['status'] ?? 'active',
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        return $this->db->execute(
            "UPDATE `categories` SET `name` = :name, `slug` = :slug, `icon` = :icon, 
                    `sort_order` = :sort_order, `status` = :status WHERE `id` = :id",
            [
                ':name' => $data['name'],
                ':slug' => $data['slug'],
                ':icon' => $data['icon'] ?? null,
                ':sort_order' => (int)($data['sort_order'] ?? 0),
                ':status' => $data['status'] ?? 'active',
                ':id' => $id,
            ]
        ) >= 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM `categories` WHERE `id` = :id", [':id' => $id]) > 0;
    }
}
