<?php

namespace App\Models;

use App\Domain\Notification\Enums\OutboxStatus;
use Carbon\CarbonInterface;
use Database\Factories\OutboxMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutboxMessage extends Model
{
    /** @use HasFactory<OutboxMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'aggregate_type',
        'aggregate_id',
        'exchange',
        'routing_key',
        'payload',
        'status',
        'attempts',
        'last_error',
        'available_at',
        'published_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'status' => OutboxStatus::class,
        'available_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function markPublished(): void
    {
        $this->forceFill([
            'status' => OutboxStatus::Published,
            'published_at' => now(),
            'last_error' => null,
        ])->save();
    }

    public function markFailed(string $error, CarbonInterface $nextAttemptAt): void
    {
        $this->forceFill([
            'status' => OutboxStatus::Failed,
            'attempts' => $this->attempts + 1,
            'last_error' => $error,
            'available_at' => $nextAttemptAt,
        ])->save();
    }
}
