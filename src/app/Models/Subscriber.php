<?php

namespace App\Models;

use App\Domain\Notification\Enums\NotificationChannel;
use Database\Factories\SubscriberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscriber extends Model
{
    /** @use HasFactory<SubscriberFactory> */
    use HasFactory;

    protected $fillable = ['email', 'phone', 'name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function contactFor(NotificationChannel $channel): ?string
    {
        return match ($channel) {
            NotificationChannel::Email => $this->email,
            NotificationChannel::Sms => $this->phone,
        };
    }

    public function hasContactFor(NotificationChannel $channel): bool
    {
        return filled($this->contactFor($channel));
    }
}
