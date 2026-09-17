<?php

declare(strict_types=1);

namespace App\Cron\Tasks;

use App\Core\Database;

class PaymentReconciliation
{
    public function run(): string
    {
        $db = Database::getInstance();
        // Expire pending payments older than 24 hours
        $affected = $db->execute(
            "UPDATE `payments` SET `status` = 'failed' WHERE `status` = 'pending' AND `created_at` < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );

        return "Payment reconciliation complete. Marked {$affected} stale payments as failed.";
    }
}
