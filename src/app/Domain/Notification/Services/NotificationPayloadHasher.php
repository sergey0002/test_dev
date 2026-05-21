<?php

namespace App\Domain\Notification\Services;

class NotificationPayloadHasher
{
    public function hash(array $payload): string
    {
        // Для идемпотентности вычисляем детерминированный hash:
        // одинаковый бизнес-payload всегда должен давать одинаковый результат.
        return hash('sha256', json_encode($this->normalize($payload), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    public function normalize(array $payload): array
    {
        if (isset($payload['recipient_ids']) && is_array($payload['recipient_ids'])) {
            // Сортируем recipient_ids, чтобы порядок не влиял на hash:
            // [1,2,3] и [3,2,1] считаем одним и тем же бизнес-запросом.
            $payload['recipient_ids'] = array_values(array_map('intval', $payload['recipient_ids']));
            sort($payload['recipient_ids']);
        }

        if (isset($payload['metadata']) && is_array($payload['metadata'])) {
            $payload['metadata'] = $this->sortRecursive($payload['metadata']);
        }

        if (isset($payload['idempotency_key']) && is_string($payload['idempotency_key'])) {
            $payload['idempotency_key'] = trim($payload['idempotency_key']);
        }

        ksort($payload);

        return $payload;
    }

    private function sortRecursive(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortRecursive($item);
            }
        }

        ksort($value);

        return $value;
    }
}
