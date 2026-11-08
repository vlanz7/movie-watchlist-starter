<?php

namespace App\Exceptions;

use RuntimeException;

class GeminiException extends RuntimeException
{
    public function __construct(string $message, public int $status = 502)
    {
        parent::__construct($message);
    }
}