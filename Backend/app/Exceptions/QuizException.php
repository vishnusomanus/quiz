<?php

namespace App\Exceptions;

use RuntimeException;

class QuizException extends RuntimeException
{
    public function __construct(string $message, int $statusCode = 400)
    {
        parent::__construct($message, $statusCode);
    }

    public function statusCode(): int
    {
        return $this->getCode() > 0 ? $this->getCode() : 400;
    }
}
