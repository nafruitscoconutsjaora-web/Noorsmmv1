<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class ReferralRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getReferralCode(int $userId): string
    {
        $stmt = $this->db->prepare("SELECT `referral_code`, `username` FROM `users` WHERE `id` = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($row['referral_code'])) {
            return $row['referral_code'];
        }

        // Generate clean referral code based on username + random hex
        $code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $row['username'] ?? 'USER'), 0, 4) . bin2hex(random_bytes(2)));
        $stmtUp = $this->db->prepare("UPDATE `users` SET `referral_code` = :code WHERE `id` = :user_id");
        $stmtUp->execute([':code' => $code, ':user_id' => $userId]);

        return $code;
    }

    public function getStats(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT 
            COUNT(*) as total_referred,
            COALESCE(SUM(total_commission_earned), 0) as total_earnings
            FROM `referrals` WHERE `referrer_id` = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // List referrals
        $stmtList = $this->db->prepare("SELECT r.*, u.username as referee_username, u.created_at as joined_at
            FROM `referrals` r
            JOIN `users` u ON r.referee_id = u.id
            WHERE r.referrer_id = :user_id
            ORDER BY r.created_at DESC");
        $stmtList->execute([':user_id' => $userId]);
        $referrals = $stmtList->fetchAll(PDO::FETCH_ASSOC);

        // Payout history
        $stmtPayouts = $this->db->prepare("SELECT * FROM `referral_payouts` WHERE `user_id` = :user_id ORDER BY `created_at` DESC");
        $stmtPayouts->execute([':user_id' => $userId]);
        $payouts = $stmtPayouts->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total_referred' => (int)($stats['total_referred'] ?? 0),
            'total_earnings' => (float)($stats['total_earnings'] ?? 0),
            'referrals' => $referrals,
            'payouts' => $payouts,
        ];
    }

    public function requestPayout(int $userId, float $amount, string $method, ?string $details = null): bool
    {
        $stmt = $this->db->prepare("INSERT INTO `referral_payouts` (`user_id`, `amount`, `method`, `payout_details`, `status`, `created_at`) 
                                   VALUES (:user_id, :amount, :method, :details, 'pending', NOW())");
        return $stmt->execute([
            ':user_id' => $userId,
            ':amount' => $amount,
            ':method' => $method,
            ':details' => $details,
        ]);
    }

    public function getAllAdmin(): array
    {
        $sql = "SELECT r.*, u1.username as referrer_name, u2.username as referee_name 
                FROM `referrals` r
                JOIN `users` u1 ON r.referrer_id = u1.id
                JOIN `users` u2 ON r.referee_id = u2.id
                ORDER BY r.created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPayoutsAdmin(): array
    {
        $sql = "SELECT rp.*, u.username 
                FROM `referral_payouts` rp
                JOIN `users` u ON rp.user_id = u.id
                ORDER BY rp.created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePayoutStatus(int $payoutId, string $status, ?string $note = null): bool
    {
        $stmt = $this->db->prepare("UPDATE `referral_payouts` SET `status` = :status, `admin_note` = :note, `updated_at` = NOW() WHERE `id` = :id");
        return $stmt->execute([
            ':status' => $status,
            ':note' => $note,
            ':id' => $payoutId,
        ]);
    }
}
