<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class LoyaltyRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getOrCreateAccount(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM `loyalty_accounts` WHERE `user_id` = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $acc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$acc) {
            // Calculate lifetime spending from orders
            $stmtSpent = $this->db->prepare("SELECT COALESCE(SUM(`charge`), 0) FROM `orders` WHERE `user_id` = :user_id AND `status` != 'cancelled'");
            $stmtSpent->execute([':user_id' => $userId]);
            $spent = (float)$stmtSpent->fetchColumn();

            $tier = 'bronze';
            if ($spent >= 10000) {
                $tier = 'platinum';
            } elseif ($spent >= 5000) {
                $tier = 'gold';
            } elseif ($spent >= 1000) {
                $tier = 'silver';
            }

            $initPoints = (int)floor($spent / 10);

            $stmtInsert = $this->db->prepare("INSERT INTO `loyalty_accounts` (`user_id`, `points`, `tier`, `lifetime_spent`, `created_at`) 
                                              VALUES (:user_id, :points, :tier, :spent, NOW())");
            $stmtInsert->execute([
                ':user_id' => $userId,
                ':points' => $initPoints,
                ':tier' => $tier,
                ':spent' => $spent,
            ]);

            return [
                'user_id' => $userId,
                'points' => $initPoints,
                'tier' => $tier,
                'lifetime_spent' => $spent,
            ];
        }

        return $acc;
    }

    public function getTransactions(int $userId, int $limit = 20): array
    {
        $stmt = $this->db->prepare("SELECT * FROM `loyalty_transactions` WHERE `user_id` = :user_id ORDER BY `created_at` DESC LIMIT :limit");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addPoints(int $userId, int $points, string $type, string $description, ?string $refId = null): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO `loyalty_transactions` (`user_id`, `points`, `type`, `description`, `reference_id`, `created_at`) 
                                       VALUES (:user_id, :points, :type, :desc, :ref, NOW())");
            $stmt->execute([
                ':user_id' => $userId,
                ':points' => $points,
                ':type' => $type,
                ':desc' => $description,
                ':ref' => $refId,
            ]);

            $stmtAcc = $this->db->prepare("UPDATE `loyalty_accounts` SET `points` = `points` + :points, `updated_at` = NOW() WHERE `user_id` = :user_id");
            $stmtAcc->execute([':points' => $points, ':user_id' => $userId]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function redeemPoints(int $userId, int $points, float $creditAmount): bool
    {
        $this->db->beginTransaction();
        try {
            $stmtCheck = $this->db->prepare("SELECT points FROM `loyalty_accounts` WHERE `user_id` = :user_id FOR UPDATE");
            $stmtCheck->execute([':user_id' => $userId]);
            $current = (int)$stmtCheck->fetchColumn();

            if ($current < $points) {
                $this->db->rollBack();
                return false;
            }

            // Deduct points
            $stmtDeduct = $this->db->prepare("UPDATE `loyalty_accounts` SET `points` = `points` - :pts WHERE `user_id` = :user_id");
            $stmtDeduct->execute([':pts' => $points, ':user_id' => $userId]);

            // Log loyalty transaction
            $stmtLog = $this->db->prepare("INSERT INTO `loyalty_transactions` (`user_id`, `points`, `type`, `description`, `created_at`) 
                                          VALUES (:user_id, :pts, 'redeem', :desc, NOW())");
            $stmtLog->execute([
                ':user_id' => $userId,
                ':pts' => -$points,
                ':desc' => "Redeemed {$points} points for ₹" . number_format($creditAmount, 2) . " wallet balance",
            ]);

            // Credit wallet
            $stmtBal = $this->db->prepare("SELECT balance FROM `users` WHERE `id` = :user_id FOR UPDATE");
            $stmtBal->execute([':user_id' => $userId]);
            $balBefore = (string)$stmtBal->fetchColumn();
            $balAfter = bcadd($balBefore, (string)$creditAmount, 8);

            $stmtUpUser = $this->db->prepare("UPDATE `users` SET `balance` = :new_bal WHERE `id` = :user_id");
            $stmtUpUser->execute([':new_bal' => $balAfter, ':user_id' => $userId]);

            // Wallet transaction record
            $stmtTx = $this->db->prepare("INSERT INTO `wallet_transactions` 
                (`user_id`, `type`, `amount`, `balance_before`, `balance_after`, `description`, `reference_id`, `status`, `created_at`)
                VALUES (:user_id, 'credit', :amount, :before, :after, :desc, :ref, 'completed', NOW())");
            $stmtTx->execute([
                ':user_id' => $userId,
                ':amount' => $creditAmount,
                ':before' => $balBefore,
                ':after' => $balAfter,
                ':desc' => "Loyalty points redemption ({$points} pts)",
                ':ref' => 'LOYALTY-' . time(),
            ]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
