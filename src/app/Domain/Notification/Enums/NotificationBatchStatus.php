<?php

namespace App\Domain\Notification\Enums;

enum NotificationBatchStatus: string
{
    case Accepted = 'accepted';
    case PartiallyAccepted = 'partially_accepted';
}
