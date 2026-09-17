<?php

declare(strict_types=1);

namespace App\Cron\Tasks;

use App\Core\Database;

class Cleanup
{
    public function run(): string
    {
        $db = Database::getInstance();
        // Clean cron logs older than 30 days
        $deleted = $db->execute("DELETE FROM `cron_logs` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        return "Cleanup completed. Removed {$deleted} old cron logs.";
    }
}
