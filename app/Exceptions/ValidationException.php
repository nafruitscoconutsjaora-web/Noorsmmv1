<?php

declare(strict_types=1);

namespace App\Exceptions;

class ValidationException extends AppException
{
    private array $errors = [];

    public function __construct(array $errors = [], string $message = "Validation failed", int $statusCode = 422)
    {
        $this->errors = $errors;
        parent::__construct($message, $statusCode);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
