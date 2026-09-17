<?php

declare(strict_types=1);

return [
    'razorpay' => [
        'enabled' => filter_var($_ENV['RAZORPAY_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'mode' => $_ENV['RAZORPAY_MODE'] ?? 'test',
        'key_id' => $_ENV['RAZORPAY_KEY_ID'] ?? '',
        'key_secret' => $_ENV['RAZORPAY_KEY_SECRET'] ?? '',
        'webhook_secret' => $_ENV['RAZORPAY_WEBHOOK_SECRET'] ?? '',
        'currency' => 'INR',
    ],
];
