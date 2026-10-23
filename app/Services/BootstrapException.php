<?php

namespace App\Services;

use RuntimeException;

class BootstrapException extends RuntimeException
{
    public const IN_PROGRESS = 'in_progress';
    public const LOCK_UNAVAILABLE = 'lock_unavailable';
    public const ALREADY_COMPLETED = 'already_completed';

    public function __construct(string $reason, ?\Throwable $previous = null)
    {
        parent::__construct($reason, 0, $previous);
    }

    public function reason(): string
    {
        return $this->getMessage();
    }
}
