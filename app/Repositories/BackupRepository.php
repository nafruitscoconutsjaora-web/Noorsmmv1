<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class BackupRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getBackups(): array
    {
        return $this->db->query("SELECT * FROM `database_backups` ORDER BY `created_at` DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createBackupRecord(string $filename, int $fileSize, int $tablesCount, int $recordsCount, string $createdBy = 'admin'): int
    {
        $stmt = $this->db->prepare("INSERT INTO `database_backups` 
            (`filename`, `file_size`, `tables_count`, `records_count`, `status`, `created_by`, `created_at`) 
            VALUES (:fn, :fs, :tc, :rc, 'completed', :cb, NOW())");
        $stmt->execute([
            ':fn' => $filename,
            ':fs' => $fileSize,
            ':tc' => $tablesCount,
            ':rc' => $recordsCount,
            ':cb' => $createdBy,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getSystemStats(): array
    {
        // Table count & row estimates
        $dbName = config('database.database', 'smm_panel');
        $stmt = $this->db->prepare("SELECT COUNT(*) as table_count, SUM(data_length + index_length) as db_size 
                                   FROM information_schema.TABLES WHERE table_schema = :db");
        $stmt->execute([':db' => $dbName]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['table_count' => 0, 'db_size' => 0];
    }
}
