<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use App\Exceptions\AppException;

class Database
{
    private static ?self $instance = null;
    private ?PDO $connection = null;
    private array $config;
    private int $transactionLevel = 0;

    private function __construct()
    {
        $this->config = config('database', [
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'smm_panel',
            'username' => 'smm_user',
            'password' => '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ]);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect(): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['host'],
            $this->config['port'],
            $this->config['database'],
            $this->config['charset']
        );

        try {
            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
        } catch (PDOException $e) {
            throw new AppException("Database connection error: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    public function beginTransaction(): bool
    {
        if ($this->transactionLevel === 0) {
            $this->transactionLevel++;
            return $this->getConnection()->beginTransaction();
        }
        $this->transactionLevel++;
        return true;
    }

    public function commit(): bool
    {
        if ($this->transactionLevel > 0) {
            $this->transactionLevel--;
            if ($this->transactionLevel === 0 && $this->getConnection()->inTransaction()) {
                return $this->getConnection()->commit();
            }
            return true;
        }
        return false;
    }

    public function rollBack(): bool
    {
        if ($this->transactionLevel > 0) {
            $this->transactionLevel = 0;
            if ($this->getConnection()->inTransaction()) {
                return $this->getConnection()->rollBack();
            }
            return true;
        }
        return false;
    }

    public function inTransaction(): bool
    {
        return $this->getConnection()->inTransaction();
    }

    /**
     * Execute a callback inside an atomic transaction with automatic rollback
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function prepare(string $sql): \PDOStatement
    {
        return $this->getConnection()->prepare($sql);
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $res = $this->query($sql, $params)->fetch();
        return $res === false ? null : $res;
    }

    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function lastInsertId(): string
    {
        return $this->getConnection()->lastInsertId();
    }
}
