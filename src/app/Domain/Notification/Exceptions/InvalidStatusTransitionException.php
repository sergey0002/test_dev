<?php

namespace App\Domain\Notification\Exceptions;

use App\Domain\Notification\Enums\NotificationStatus;
use RuntimeException;

class InvalidStatusTransitionException extends RuntimeException
{
    public function __construct(NotificationStatus $from, NotificationStatus $to)
    {
        parent::__construct("Запрещенный переход статуса: {$from->value} -> {$to->value}.");
    }
}
