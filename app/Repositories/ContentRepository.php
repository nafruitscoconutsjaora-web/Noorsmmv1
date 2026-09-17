<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class ContentRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getPublishedArticles(?string $category = null): array
    {
        $sql = "SELECT * FROM `knowledge_base_articles` WHERE `is_published` = 1";
        $params = [];
        if ($category) {
            $sql .= " AND `category` = :cat";
            $params[':cat'] = $category;
        }
        $sql .= " ORDER BY `sort_order` ASC, `created_at` DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findArticleBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `knowledge_base_articles` WHERE `slug` = :slug AND `is_published` = 1");
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->db->prepare("UPDATE `knowledge_base_articles` SET `views_count` = `views_count` + 1 WHERE `id` = :id")->execute([':id' => $row['id']]);
        }
        return $row ?: null;
    }

    public function getPublishedFaqs(?string $category = null): array
    {
        $sql = "SELECT * FROM `faqs` WHERE `is_published` = 1";
        $params = [];
        if ($category) {
            $sql .= " AND `category` = :cat";
            $params[':cat'] = $category;
        }
        $sql .= " ORDER BY `sort_order` ASC, `id` ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllArticlesAdmin(): array
    {
        return $this->db->query("SELECT * FROM `knowledge_base_articles` ORDER BY `sort_order` ASC, `id` DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllFaqsAdmin(): array
    {
        return $this->db->query("SELECT * FROM `faqs` ORDER BY `sort_order` ASC, `id` DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveArticle(array $data): bool
    {
        if (!empty($data['id'])) {
            $stmt = $this->db->prepare("UPDATE `knowledge_base_articles` 
                SET `category` = :cat, `title` = :title, `slug` = :slug, `content` = :content, `is_published` = :pub, `sort_order` = :sort, `updated_at` = NOW() 
                WHERE `id` = :id");
            return $stmt->execute([
                ':cat' => $data['category'],
                ':title' => $data['title'],
                ':slug' => $data['slug'],
                ':content' => $data['content'],
                ':pub' => !empty($data['is_published']) ? 1 : 0,
                ':sort' => (int)($data['sort_order'] ?? 0),
                ':id' => $data['id'],
            ]);
        }

        $stmt = $this->db->prepare("INSERT INTO `knowledge_base_articles` 
            (`category`, `title`, `slug`, `content`, `is_published`, `sort_order`, `created_at`) 
            VALUES (:cat, :title, :slug, :content, :pub, :sort, NOW())");
        return $stmt->execute([
            ':cat' => $data['category'],
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':content' => $data['content'],
            ':pub' => !empty($data['is_published']) ? 1 : 0,
            ':sort' => (int)($data['sort_order'] ?? 0),
        ]);
    }

    public function saveFaq(array $data): bool
    {
        if (!empty($data['id'])) {
            $stmt = $this->db->prepare("UPDATE `faqs` 
                SET `category` = :cat, `question` = :q, `answer` = :a, `is_published` = :pub, `sort_order` = :sort, `updated_at` = NOW() 
                WHERE `id` = :id");
            return $stmt->execute([
                ':cat' => $data['category'],
                ':q' => $data['question'],
                ':a' => $data['answer'],
                ':pub' => !empty($data['is_published']) ? 1 : 0,
                ':sort' => (int)($data['sort_order'] ?? 0),
                ':id' => $data['id'],
            ]);
        }

        $stmt = $this->db->prepare("INSERT INTO `faqs` 
            (`category`, `question`, `answer`, `is_published`, `sort_order`, `created_at`) 
            VALUES (:cat, :q, :a, :pub, :sort, NOW())");
        return $stmt->execute([
            ':cat' => $data['category'],
            ':q' => $data['question'],
            ':a' => $data['answer'],
            ':pub' => !empty($data['is_published']) ? 1 : 0,
            ':sort' => (int)($data['sort_order'] ?? 0),
        ]);
    }
}
