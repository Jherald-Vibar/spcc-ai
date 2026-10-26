<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class AiServiceException extends RuntimeException
{
    /** @param bool $retryable false for problems a retry cannot fix (unreadable file, bad request) */
    public function __construct(string $message, public readonly bool $retryable = true, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
