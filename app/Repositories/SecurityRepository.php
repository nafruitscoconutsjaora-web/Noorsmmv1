<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class SecurityRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function logLogin(int $userId, string $ip, ?string $userAgent, ?string $sessionId, string $status = 'success'): void
    {
        $stmt = $this->db->prepare("INSERT INTO `user_logins` (`user_id`, `ip_address`, `user_agent`, `session_id`, `status`, `created_at`) 
                                   VALUES (:user_id, :ip, :ua, :sid, :status, NOW())");
        $stmt->execute([
            ':user_id' => $userId,
            ':ip' => $ip,
            ':ua' => mb_substr($userAgent ?? '', 0, 255),
            ':sid' => $sessionId,
            ':status' => $status,
        ]);
    }

    public function getLoginHistory(int $userId, int $limit = 25): array
    {
        $stmt = $this->db->prepare("SELECT * FROM `user_logins` WHERE `user_id` = :user_id ORDER BY `created_at` DESC LIMIT :limit");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPreferences(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM `user_preferences` WHERE `user_id` = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $default = [
                'user_id' => $userId,
                'notify_order_status' => 1,
                'notify_wallet_deposit' => 1,
                'notify_ticket_reply' => 1,
                'notify_news' => 1,
                'language' => 'en',
                'timezone' => 'UTC',
                'display_density' => 'normal',
            ];
            $stmtInsert = $this->db->prepare("INSERT INTO `user_preferences` 
                (`user_id`, `notify_order_status`, `notify_wallet_deposit`, `notify_ticket_reply`, `notify_news`, `language`, `timezone`, `display_density`, `created_at`) 
                VALUES (:user_id, 1, 1, 1, 1, 'en', 'UTC', 'normal', NOW())");
            $stmtInsert->execute([':user_id' => $userId]);
            return $default;
        }

        return $row;
    }

    public function updatePreferences(int $userId, array $data): bool
    {
        $stmt = $this->db->prepare("INSERT INTO `user_preferences` 
            (`user_id`, `notify_order_status`, `notify_wallet_deposit`, `notify_ticket_reply`, `notify_news`, `language`, `timezone`, `display_density`, `created_at`, `updated_at`)
            VALUES (:user_id, :nos, :nwd, :ntr, :nn, :lang, :tz, :dd, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
            `notify_order_status` = VALUES(`notify_order_status`),
            `notify_wallet_deposit` = VALUES(`notify_wallet_deposit`),
            `notify_ticket_reply` = VALUES(`notify_ticket_reply`),
            `notify_news` = VALUES(`notify_news`),
            `language` = VALUES(`language`),
            `timezone` = VALUES(`timezone`),
            `display_density` = VALUES(`display_density`),
            `updated_at` = NOW()");
        return $stmt->execute([
            ':user_id' => $userId,
            ':nos' => !empty($data['notify_order_status']) ? 1 : 0,
            ':nwd' => !empty($data['notify_wallet_deposit']) ? 1 : 0,
            ':ntr' => !empty($data['notify_ticket_reply']) ? 1 : 0,
            ':nn' => !empty($data['notify_news']) ? 1 : 0,
            ':lang' => $data['language'] ?? 'en',
            ':tz' => $data['timezone'] ?? 'UTC',
            ':dd' => $data['display_density'] ?? 'normal',
        ]);
    }

    public function logSecurityEvent(string $eventType, string $severity, ?int $userId, ?int $adminId, string $ip, ?string $ua, ?array $details = null): void
    {
        $stmt = $this->db->prepare("INSERT INTO `security_events` (`event_type`, `severity`, `user_id`, `admin_id`, `ip_address`, `user_agent`, `details`, `created_at`) 
                                   VALUES (:type, :sev, :uid, :aid, :ip, :ua, :details, NOW())");
        $stmt->execute([
            ':type' => $eventType,
            ':sev' => $severity,
            ':uid' => $userId,
            ':aid' => $adminId,
            ':ip' => $ip,
            ':ua' => mb_substr($ua ?? '', 0, 255),
            ':details' => $details ? json_encode($details) : null,
        ]);
    }

    public function getSecurityEventsAdmin(int $limit = 50): array
    {
        $sql = "SELECT se.*, u.username as user_name, a.username as admin_name 
                FROM `security_events` se
                LEFT JOIN `users` u ON se.user_id = u.id
                LEFT JOIN `admins` a ON se.admin_id = a.id
                ORDER BY se.created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Detect multiple accounts from the same IP
     */
    public function getMultiAccountIndicators(): array
    {
        $sql = "SELECT ip_address, COUNT(DISTINCT user_id) as users_count, GROUP_CONCAT(DISTINCT user_id) as user_ids
                FROM `user_logins`
                WHERE `ip_address` NOT IN ('127.0.0.1', '::1')
                GROUP BY ip_address
                HAVING users_count > 1
                ORDER BY users_count DESC
                LIMIT 15";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
