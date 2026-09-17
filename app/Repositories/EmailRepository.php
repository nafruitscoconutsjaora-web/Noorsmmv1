<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class EmailRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getTemplates(): array
    {
        return $this->db->query("SELECT * FROM `email_templates` ORDER BY `id` ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateTemplate(int $id, string $subject, string $body, bool $isActive): bool
    {
        $stmt = $this->db->prepare("UPDATE `email_templates` SET `subject` = :sub, `body` = :body, `is_active` = :act, `updated_at` = NOW() WHERE `id` = :id");
        return $stmt->execute([
            ':sub' => $subject,
            ':body' => $body,
            ':act' => $isActive ? 1 : 0,
            ':id' => $id,
        ]);
    }

    public function logEmail(string $recipient, string $subject, ?string $templateKey, string $status, ?string $error = null): void
    {
        $stmt = $this->db->prepare("INSERT INTO `email_logs` (`recipient`, `subject`, `template_key`, `status`, `error_message`, `created_at`) 
            VALUES (:to, :sub, :tpl, :status, :err, NOW())");
        $stmt->execute([
            ':to' => $recipient,
            ':sub' => $subject,
            ':tpl' => $templateKey,
            ':status' => $status,
            ':err' => $error,
        ]);
    }

    public function getLogs(int $limit = 50): array
    {
        $stmt = $this->db->prepare("SELECT * FROM `email_logs` ORDER BY `created_at` DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
