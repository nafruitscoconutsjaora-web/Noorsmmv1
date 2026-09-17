<?php

declare(strict_types=1);

namespace App\Support;

class Encryption
{
    private static function getKey(): string
    {
        $key = config('app.key');
        if (empty($key)) {
            $key = 'smm_default_encryption_key_2026';
        }
        return hash('sha256', $key, true);
    }

    public static function encrypt(string $plainText): string
    {
        $key = self::getKey();
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plainText, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipher);
    }

    public static function decrypt(string $cipherText): ?string
    {
        $raw = base64_decode($cipherText);
        if ($raw === false || strlen($raw) < 17) {
            return null;
        }

        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $key = self::getKey();
        $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $plain === false ? null : $plain;
    }
}
