<?php

namespace App\Domain\Notification\Exceptions;

use RuntimeException;

class IdempotencyConflictException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Этот idempotency_key уже использован с другим payload.');
    }
}
