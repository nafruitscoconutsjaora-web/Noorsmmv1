<?php

declare(strict_types=1);

namespace App\Exceptions;

class AuthenticationException extends AppException
{
    public function __construct(string $message = "Unauthenticated", int $statusCode = 401)
    {
        parent::__construct($message, $statusCode);
    }
}
