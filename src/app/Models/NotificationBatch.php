<?php

namespace App\Models;

use App\Domain\Notification\Enums\NotificationBatchStatus;
use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Enums\NotificationType;
use Database\Factories\NotificationBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationBatch extends Model
{
    /** @use HasFactory<NotificationBatchFactory> */
    use HasFactory;

    protected $fillable = [
        'idempotency_key',
        'payload_hash',
        'channel',
        'type',
        'message',
        'requested_count',
        'accepted_count',
        'status',
        'metadata',
    ];

    protected $casts = [
        'channel' => NotificationChannel::class,
        'type' => NotificationType::class,
        'status' => NotificationBatchStatus::class,
        'metadata' => 'array',
    ];

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'batch_id');
    }
}
