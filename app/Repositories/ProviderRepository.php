<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class ProviderRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function getAll(): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, (SELECT COUNT(*) FROM `services` s WHERE s.provider_id = p.id) as service_count 
             FROM `providers` p ORDER BY p.id ASC"
        );
    }

    public function getAllActive(): array
    {
        return $this->db->fetchAll("SELECT * FROM `providers` WHERE `status` = 'active' ORDER BY `id` ASC");
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM `providers` WHERE `id` = :id", [':id' => $id]);
    }

    public function create(array $data): int
    {
        $this->db->execute(
            "INSERT INTO `providers` (`name`, `api_url`, `api_key`, `currency`, `status`) 
             VALUES (:name, :url, :key, :curr, :status)",
            [
                ':name' => $data['name'],
                ':url' => $data['api_url'],
                ':key' => $data['api_key'],
                ':curr' => $data['currency'] ?? 'USD',
                ':status' => $data['status'] ?? 'active',
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [
            'name' => $data['name'],
            'api_url' => $data['api_url'],
            'currency' => $data['currency'] ?? 'USD',
            'status' => $data['status'] ?? 'active',
        ];
        $params = [
            ':name' => $fields['name'],
            ':url' => $fields['api_url'],
            ':curr' => $fields['currency'],
            ':status' => $fields['status'],
            ':id' => $id,
        ];

        $sql = "UPDATE `providers` SET `name` = :name, `api_url` = :url, `currency` = :curr, `status` = :status";
        if (!empty($data['api_key'])) {
            $sql .= ", `api_key` = :key";
            $params[':key'] = $data['api_key'];
        }
        $sql .= " WHERE `id` = :id";

        return $this->db->execute($sql, $params) >= 0;
    }

    public function updateBalance(int $id, string $balance): void
    {
        $this->db->execute(
            "UPDATE `providers` SET `balance` = :bal, `last_synced_at` = NOW() WHERE `id` = :id",
            [':bal' => $balance, ':id' => $id]
        );
    }

    public function updateSyncStatus(int $id, string $status, ?string $error = null): void
    {
        $this->db->execute(
            "UPDATE `providers` SET `last_synced_at` = NOW(), `last_sync_status` = :status, `last_sync_error` = :err WHERE `id` = :id",
            [':status' => $status, ':err' => $error, ':id' => $id]
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM `providers` WHERE `id` = :id", [':id' => $id]) > 0;
    }
}
