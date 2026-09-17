<?php

declare(strict_types=1);

return [
    'timeout_seconds' => 30,
    'retry_attempts' => 3,
    'drivers' => [
        'generic' => \App\Providers\Drivers\GenericProvider::class,
    ],
];
