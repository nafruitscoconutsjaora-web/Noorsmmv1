<?php

declare(strict_types=1);

return [
    'session_lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),
    'password_algo' => PASSWORD_BCRYPT,
    'password_options' => [
        'cost' => 12,
    ],
    'lockout_threshold' => 5,
    'lockout_duration_seconds' => 900,
];
