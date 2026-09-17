<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    private string $method;
    private string $uri;
    private string $path;
    private array $queryParams;
    private array $postParams;
    private ?array $jsonParams = null;
    private array $headers;
    private array $server;

    public function __construct()
    {
        $this->server = $_SERVER;
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        
        // Method override support (_method in POST)
        if ($this->method === 'POST' && isset($_POST['_method'])) {
            $this->method = strtoupper($_POST['_method']);
        }

        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($this->uri, PHP_URL_PATH) ?? '/';
        $this->path = '/' . trim($path, '/');
        if ($this->path === '') {
            $this->path = '/';
        }

        $this->queryParams = $_GET;
        $this->postParams = $_POST;
        $this->headers = $this->parseHeaders();
    }

    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $val) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $val;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower($key))));
                $headers[$header] = $val;
            }
        }
        return $headers;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function path(): string
    {
        return $this->path;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function query(string $key = '', mixed $default = null): mixed
    {
        if ($key === '') {
            return $this->queryParams;
        }
        return $this->queryParams[$key] ?? $default;
    }

    public function post(string $key = '', mixed $default = null): mixed
    {
        if ($key === '') {
            return $this->postParams;
        }
        return $this->postParams[$key] ?? $default;
    }

    public function input(string $key = '', mixed $default = null): mixed
    {
        $all = $this->all();
        if ($key === '') {
            return $all;
        }
        return $all[$key] ?? $default;
    }

    public function json(): array
    {
        if ($this->jsonParams === null) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            $this->jsonParams = is_array($decoded) ? $decoded : [];
        }
        return $this->jsonParams;
    }

    public function all(): array
    {
        return array_merge($this->queryParams, $this->postParams, $this->json());
    }

    public function header(string $key, ?string $default = null): ?string
    {
        $formatted = str_replace(' ', '-', ucwords(str_replace('-', ' ', strtolower($key))));
        return $this->headers[$formatted] ?? $default;
    }

    public function ip(): string
    {
        if (!empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $this->server['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        if (!empty($this->server['HTTP_CLIENT_IP'])) {
            return $this->server['HTTP_CLIENT_IP'];
        }
        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function isAjax(): bool
    {
        return ($this->header('X-Requested-With') === 'XMLHttpRequest') ||
               str_contains($this->header('Accept') ?? '', 'application/json');
    }
}
