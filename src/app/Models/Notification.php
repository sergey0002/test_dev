<?php

namespace App\Models;

use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Enums\NotificationStatus;
use App\Domain\Notification\Enums\NotificationType;
use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'subscriber_id',
        'channel',
        'type',
        'message_snapshot',
        'status',
        'priority',
        'provider_message_id',
        'attempts_count',
        'max_attempts',
        'last_error_code',
        'last_error_message',
        'queued_at',
        'sent_at',
        'delivered_at',
        'dropped_at',
    ];

    protected $casts = [
        'channel' => NotificationChannel::class,
        'type' => NotificationType::class,
        'status' => NotificationStatus::class,
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'dropped_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(NotificationBatch::class, 'batch_id');
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(NotificationEvent::class);
    }

    public function providerAttempts(): HasMany
    {
        return $this->hasMany(ProviderAttempt::class);
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }
}
