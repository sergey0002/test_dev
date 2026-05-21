<?php

namespace App\Models;

use App\Domain\Notification\Enums\ProviderAttemptResult;
use Database\Factories\ProviderAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderAttempt extends Model
{
    /** @use HasFactory<ProviderAttemptFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'notification_id',
        'attempt_no',
        'provider',
        'result',
        'provider_message_id',
        'error_code',
        'error_message',
        'duration_ms',
    ];

    protected $casts = [
        'result' => ProviderAttemptResult::class,
        'created_at' => 'datetime',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
