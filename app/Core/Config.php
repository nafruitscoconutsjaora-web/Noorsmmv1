<?php

declare(strict_types=1);

namespace App\Core;

class Config
{
    private array $items = [];

    public function __construct(string $configPath)
    {
        $this->loadDotEnv(dirname($configPath) . '/.env');
        $this->loadConfigFiles($configPath);
    }

    private function loadDotEnv(string $envFile): void
    {
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $val] = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);
                // Strip surrounding quotes
                if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                    (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                    $val = substr($val, 1, -1);
                }
                $_ENV[$key] = $val;
                putenv("{$key}={$val}");
            }
        }
    }

    private function loadConfigFiles(string $configPath): void
    {
        $files = glob($configPath . '/*.php');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $key = basename($file, '.php');
            $this->items[$key] = require $file;
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $current = $this->items;

        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return $default;
            }
            $current = $current[$part];
        }

        return $current;
    }

    public function set(string $key, mixed $value): void
    {
        $parts = explode('.', $key);
        $current = &$this->items;

        foreach ($parts as $part) {
            if (!isset($current[$part]) || !is_array($current[$part])) {
                $current[$part] = [];
            }
            $current = &$current[$part];
        }

        $current = $value;
    }

    public function all(): array
    {
        return $this->items;
    }
}
