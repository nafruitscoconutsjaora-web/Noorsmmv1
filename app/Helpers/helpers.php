<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Session;
use App\Core\View;
use App\Support\Money;

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Application::getInstance()->getConfig()->get($key, $default);
    }
}

if (!function_exists('app')) {
    function app(): Application
    {
        return Application::getInstance();
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = [], ?string $layout = null): \App\Core\Response
    {
        $content = Application::getInstance()->getView()->render($template, $data, $layout);
        return new \App\Core\Response($content);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $statusCode = 302): \App\Core\Response
    {
        $response = new \App\Core\Response();
        return $response->redirect($url, $statusCode);
    }
}

if (!function_exists('json_response')) {
    function json_response(mixed $data, int $statusCode = 200): \App\Core\Response
    {
        $response = new \App\Core\Response();
        return $response->json($data, $statusCode);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Session::csrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = csrf_token();
        return '<input type="hidden" name="_csrf_token" value="' . e($token) . '">';
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        return Session::get('user');
    }
}

if (!function_exists('auth_admin')) {
    function auth_admin(): ?array
    {
        return Session::get('admin');
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $message = null): mixed
    {
        if ($message === null) {
            return Session::flash($key);
        }
        Session::setFlash($key, $message);
        return null;
    }
}

if (!function_exists('format_currency')) {
    function format_currency(string|float|int $amount, string $currency = 'INR'): string
    {
        return Money::format($amount, $currency);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $theme = config('app.theme', 'classic');
        return '/assets/themes/' . $theme . '/' . ltrim($path, '/');
    }
}

if (!function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        static $settings = null;
        if ($settings === null) {
            try {
                $db = \App\Core\Database::getInstance()->getConnection();
                $stmt = $db->query("SELECT `key`, `value` FROM `settings`");
                $settings = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $settings[$row['key']] = $row['value'];
                }
            } catch (\Throwable $e) {
                $settings = [];
            }
        }
        return $settings[$key] ?? $default;
    }
}

if (!function_exists('mask_username')) {
    function mask_username(string $username): string
    {
        $len = mb_strlen($username);
        if ($len <= 2) {
            return mb_substr($username, 0, 1) . '***';
        }
        if ($len <= 4) {
            return mb_substr($username, 0, 2) . '***';
        }
        return mb_substr($username, 0, 3) . '***';
    }
}

if (!function_exists('session')) {
    function session(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_SESSION ?? [];
        }
        return Session::get($key, $default);
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        static $oldData = null;
        if ($oldData === null) {
            $raw = Session::flash('old');
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $oldData = is_array($decoded) ? $decoded : [];
            } elseif (is_array($raw)) {
                $oldData = $raw;
            } else {
                $oldData = [];
            }
        }
        return $oldData[$key] ?? $default;
    }
}

if (!function_exists('order_status_badge')) {
    function order_status_badge(string $status): string
    {
        return match (strtolower($status)) {
            'pending' => 'bg-amber-500/10 text-amber-400 border border-amber-500/20',
            'processing', 'in_progress' => 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
            'completed' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
            'partial' => 'bg-purple-500/10 text-purple-400 border border-purple-500/20',
            'cancelled', 'refunded', 'failed' => 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
            default => 'bg-slate-800 text-slate-300',
        };
    }
}
