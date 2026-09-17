<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class SettingRepository
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function getAll(): array
    {
        $rows = $this->db->fetchAll("SELECT `key`, `value` FROM `settings`");
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }
        return $settings;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $row = $this->db->fetchOne("SELECT `value` FROM `settings` WHERE `key` = :key", [':key' => $key]);
        return $row !== null ? $row['value'] : $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->db->execute(
            "INSERT INTO `settings` (`key`, `value`) VALUES (:key, :val) 
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
            [':key' => $key, ':val' => (string)$value]
        );
    }

    public function updateBatch(array $settings): void
    {
        foreach ($settings as $k => $v) {
            $this->set((string)$k, $v);
        }
    }
}
