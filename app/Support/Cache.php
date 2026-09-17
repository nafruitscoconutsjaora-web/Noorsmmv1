<?php

declare(strict_types=1);

namespace App\Support;

class Cache
{
    private static string $cacheDir = '';

    private static function init(): void
    {
        if (self::$cacheDir === '') {
            self::$cacheDir = dirname(__DIR__, 2) . '/storage/cache';
            if (!is_dir(self::$cacheDir)) {
                mkdir(self::$cacheDir, 0755, true);
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::init();
        $file = self::$cacheDir . '/' . md5($key) . '.cache';
        if (!file_exists($file)) {
            return $default;
        }

        $raw = file_get_contents($file);
        if ($raw === false) {
            return $default;
        }

        $data = @unserialize($raw);
        if ($data === false || !is_array($data) || !isset($data['expires_at'], $data['value'])) {
            return $default;
        }

        if ($data['expires_at'] !== 0 && time() > $data['expires_at']) {
            @unlink($file);
            return $default;
        }

        return $data['value'];
    }

    public static function set(string $key, mixed $value, int $ttlSeconds = 3600): bool
    {
        self::init();
        $file = self::$cacheDir . '/' . md5($key) . '.cache';
        $data = [
            'expires_at' => $ttlSeconds > 0 ? time() + $ttlSeconds : 0,
            'value' => $value,
        ];

        return (bool)file_put_contents($file, serialize($data), LOCK_EX);
    }

    public static function delete(string $key): bool
    {
        self::init();
        $file = self::$cacheDir . '/' . md5($key) . '.cache';
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }

    public static function flush(): void
    {
        self::init();
        $files = glob(self::$cacheDir . '/*.cache');
        if ($files !== false) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
}
