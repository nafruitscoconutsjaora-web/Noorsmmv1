<?php

declare(strict_types=1);

return [
    'csrf_token_name' => '_csrf_token',
    'rate_limit_requests' => (int)($_ENV['RATE_LIMIT_PER_MINUTE'] ?? 60),
    'rate_limit_window' => 60,
    'allowed_upload_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'],
    'max_upload_size' => 2 * 1024 * 1024, // 2MB
];
