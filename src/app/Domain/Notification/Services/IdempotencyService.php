<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Exceptions\IdempotencyConflictException;
use App\Models\NotificationBatch;

class IdempotencyService
{
    public function findExisting(string $key): ?NotificationBatch
    {
        return NotificationBatch::query()->where('idempotency_key', trim($key))->first();
    }

    public function assertPayloadMatches(NotificationBatch $batch, string $hash): void
    {
        // Один idempotency_key обязан всегда соответствовать одному и тому же payload.
        // Если hash не совпал, значит клиент переиспользовал ключ некорректно.
        if (! hash_equals($batch->payload_hash, $hash)) {
            throw new IdempotencyConflictException();
        }
    }
}
