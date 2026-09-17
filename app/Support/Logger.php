<?php

declare(strict_types=1);

namespace App\Support;

class Logger
{
    private static string $logDir = '';

    private static function init(): void
    {
        if (self::$logDir === '') {
            self::$logDir = dirname(__DIR__, 2) . '/storage/logs';
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0755, true);
            }
        }
    }

    public static function info(string $message, array $context = [], string $channel = 'app'): void
    {
        self::log('INFO', $message, $context, $channel);
    }

    public static function error(string $message, array $context = [], string $channel = 'app'): void
    {
        self::log('ERROR', $message, $context, $channel);
    }

    public static function warning(string $message, array $context = [], string $channel = 'app'): void
    {
        self::log('WARNING', $message, $context, $channel);
    }

    public static function debug(string $message, array $context = [], string $channel = 'app'): void
    {
        self::log('DEBUG', $message, $context, $channel);
    }

    public static function log(string $level, string $message, array $context = [], string $channel = 'app'): void
    {
        self::init();
        $date = date('Y-m-d');
        $time = date('Y-m-d H:i:s');
        $sanitizedContext = self::sanitize($context);

        $line = sprintf(
            "[%s] [%s.%s]: %s %s\n",
            $time,
            strtoupper($channel),
            strtoupper($level),
            $message,
            empty($sanitizedContext) ? '' : json_encode($sanitizedContext, JSON_UNESCAPED_SLASHES)
        );

        $file = self::$logDir . '/' . $channel . '-' . $date . '.log';
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    private static function sanitize(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'api_key', 'key_secret', 'secret', 'token', 'cvv', 'card'];
        $clean = [];

        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $clean[$key] = self::sanitize($val);
            } elseif (in_array(strtolower((string)$key), $sensitiveKeys, true)) {
                $clean[$key] = '[REDACTED]';
            } else {
                $clean[$key] = $val;
            }
        }

        return $clean;
    }
}
