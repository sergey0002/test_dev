<?php

namespace App\Http\Requests;

use App\Application\DTO\CreateNotificationBatchData;
use App\Domain\Notification\Enums\NotificationChannel;
use App\Domain\Notification\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreNotificationBatchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'channel' => ['required', 'string', new Enum(NotificationChannel::class)],
            'type' => ['required', 'string', new Enum(NotificationType::class)],
            'message' => ['required', 'string', 'min:1', 'max:1000'],
            'recipient_ids' => ['required', 'array', 'min:1', 'max:10000'],
            'recipient_ids.*' => ['required', 'integer', 'distinct', 'exists:subscribers,id'],
            'idempotency_key' => ['required', 'string', 'max:128'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function toData(): CreateNotificationBatchData
    {
        // Готовим DTO из валидированного payload, чтобы в action приходили уже чистые данные.
        $validated = $this->validated();

        return new CreateNotificationBatchData(
            channel: NotificationChannel::from($validated['channel']),
            type: NotificationType::from($validated['type']),
            message: $validated['message'],
            recipientIds: array_map('intval', $validated['recipient_ids']),
            idempotencyKey: $validated['idempotency_key'],
            metadata: $validated['metadata'] ?? [],
        );
    }
}
