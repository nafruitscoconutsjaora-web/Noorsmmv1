<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

class AppException extends Exception
{
    protected int $statusCode = 500;

    public function __construct(string $message = "", int $statusCode = 500, ?Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
