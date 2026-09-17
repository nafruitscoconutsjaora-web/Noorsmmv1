<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'Apex SMM Services',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:3000',
    'timezone' => $_ENV['PANEL_TIMEZONE'] ?? 'Asia/Kolkata',
    'theme' => $_ENV['THEME'] ?? 'classic',
    'currency' => $_ENV['DEFAULT_CURRENCY'] ?? 'INR',
    'key' => $_ENV['APP_KEY'] ?? '',
];
