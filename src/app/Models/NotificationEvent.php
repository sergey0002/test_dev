<?php

namespace App\Models;

use App\Domain\Notification\Enums\NotificationStatus;
use Database\Factories\NotificationEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationEvent extends Model
{
    /** @use HasFactory<NotificationEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['notification_id', 'from_status', 'to_status', 'reason', 'meta'];

    protected $casts = [
        'from_status' => NotificationStatus::class,
        'to_status' => NotificationStatus::class,
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
