<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\BackupRepository;
use PDO;

class AdminMaintenanceController extends BaseController
{
    private BackupRepository $backupRepo;
    private PDO $db;

    public function __construct()
    {
        $this->backupRepo = new BackupRepository();
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(Request $request): Response
    {
        $backups = $this->backupRepo->getBackups();
        $stats = $this->backupRepo->getSystemStats();
        $isMaintenance = (bool)setting('maintenance_mode', false);

        return view('admin/maintenance/index', [
            'backups' => $backups,
            'stats' => $stats,
            'is_maintenance' => $isMaintenance,
        ], 'admin');
    }

    public function toggleMaintenance(Request $request): Response
    {
        $current = (bool)setting('maintenance_mode', false);
        $new = $current ? '0' : '1';

        $stmt = $this->db->prepare("INSERT INTO `settings` (`key`, `value`, `updated_at`) 
            VALUES ('maintenance_mode', :val, NOW()) 
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = NOW()");
        $stmt->execute([':val' => $new]);

        $statusMsg = $new === '1' ? 'enabled' : 'disabled';
        flash('success', "Maintenance mode has been {$statusMsg}.");
        return $this->redirect('/admin/maintenance');
    }

    public function createBackup(Request $request): Response
    {
        $admin = $this->admin();
        $backupDir = dirname(__DIR__, 2) . '/storage/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $filename = 'smm_backup_' . date('Y-m-d_His') . '.sql';
        $filePath = $backupDir . '/' . $filename;

        // Get table list
        $tables = $this->db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $totalRecords = 0;

        $fp = fopen($filePath, 'w');
        fwrite($fp, "-- Apex SMM Panel Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($tables as $table) {
            // Table structure
            $createTable = $this->db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n" . $createTable['Create Table'] . ";\n\n");

            // Table data
            $rows = $this->db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            $totalRecords += count($rows);

            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $keys = array_map(fn($k) => "`{$k}`", array_keys($row));
                    $vals = array_map(function ($v) {
                        if ($v === null) return 'NULL';
                        return "'" . addslashes((string)$v) . "'";
                    }, array_values($row));
                    fwrite($fp, "INSERT INTO `{$table}` (" . implode(',', $keys) . ") VALUES (" . implode(',', $vals) . ");\n");
                }
                fwrite($fp, "\n");
            }
        }
        fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fp);

        $fileSize = filesize($filePath) ?: 0;
        $this->backupRepo->createBackupRecord($filename, $fileSize, count($tables), $totalRecords, $admin['username'] ?? 'admin');

        flash('success', "Database backup created successfully: {$filename} (" . round($fileSize / 1024, 1) . " KB, {$totalRecords} rows).");
        return $this->redirect('/admin/maintenance');
    }
}
