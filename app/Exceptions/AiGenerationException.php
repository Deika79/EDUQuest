<?php

namespace App\Exceptions;

use RuntimeException;

class AiGenerationException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly string $userMessage,
    ) {
        parent::__construct($userMessage);
    }
}
