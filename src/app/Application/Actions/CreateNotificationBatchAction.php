<?php

namespace App\Application\Actions;

use App\Application\DTO\CreateNotificationBatchData;
use App\Domain\Notification\Enums\NotificationBatchStatus;
use App\Domain\Notification\Enums\NotificationStatus;
use App\Domain\Notification\Enums\OutboxStatus;
use App\Domain\Notification\Services\IdempotencyService;
use App\Domain\Notification\Services\NotificationPayloadHasher;
use App\Domain\Notification\Services\RoutingKeyResolver;
use App\Models\NotificationBatch;
use App\Models\Subscriber;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CreateNotificationBatchAction
{
    public function __construct(
        private readonly IdempotencyService $idempotency,
        private readonly NotificationPayloadHasher $hasher,
        private readonly RoutingKeyResolver $routing,
    ) {
    }

    public function execute(CreateNotificationBatchData $data): NotificationBatch
    {
        // Считаем детерминированный hash payload для идемпотентности.
        $hash = $this->hasher->hash($data->toHashPayload());
        $existing = $this->idempotency->findExisting($data->idempotencyKey);

        if ($existing !== null) {
            // Если ключ уже есть, то payload должен совпадать; иначе это конфликт.
            $this->idempotency->assertPayloadMatches($existing, $hash);

            return $existing->load('notifications');
        }

        try {
            return $this->createBatch($data, $hash);
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23505') {
                throw $exception;
            }

            // Гонка по unique-ключу: параллельный запрос уже успел создать batch.
            $existing = $this->idempotency->findExisting($data->idempotencyKey);

            if ($existing === null) {
                throw $exception;
            }

            $this->idempotency->assertPayloadMatches($existing, $hash);

            return $existing->load('notifications');
        }
    }

    private function createBatch(CreateNotificationBatchData $data, string $hash): NotificationBatch
    {
        // В одной транзакции создаем batch, notifications, events и outbox:
        // это защищает от "полусозданного" состояния при ошибках.
        return DB::transaction(function () use ($data, $hash): NotificationBatch {
            $subscribers = Subscriber::query()
                ->whereIn('id', $data->recipientIds)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            $batch = NotificationBatch::query()->create([
                'idempotency_key' => trim($data->idempotencyKey),
                'payload_hash' => $hash,
                'channel' => $data->channel,
                'type' => $data->type,
                'message' => $data->message,
                'requested_count' => count($data->recipientIds),
                'accepted_count' => $subscribers->count(),
                'status' => $subscribers->count() === count($data->recipientIds)
                    ? NotificationBatchStatus::Accepted
                    : NotificationBatchStatus::PartiallyAccepted,
                'metadata' => $data->metadata,
            ]);

            foreach ($subscribers as $subscriber) {
                $notification = $batch->notifications()->create([
                    'subscriber_id' => $subscriber->id,
                    'channel' => $data->channel,
                    'type' => $data->type,
                    'message_snapshot' => $data->message,
                    'status' => NotificationStatus::Queued,
                    'priority' => $this->routing->priority($data->type),
                    'attempts_count' => 0,
                    'max_attempts' => config('notifications.max_attempts', 3),
                    'queued_at' => now(),
                ]);

                $notification->events()->create([
                    'from_status' => null,
                    'to_status' => NotificationStatus::Queued,
                    'reason' => 'created',
                    'meta' => ['batch_id' => $batch->id],
                ]);

                // Outbox пишем в БД в той же транзакции, что и уведомление.
                // Затем отдельный publisher уже безопасно отправляет это в RabbitMQ.
                \App\Models\OutboxMessage::query()->create([
                    'aggregate_type' => 'notification',
                    'aggregate_id' => $notification->id,
                    'exchange' => $this->routing->exchange(),
                    'routing_key' => $this->routing->routingKey($data->type),
                    'payload' => [
                        'notification_id' => $notification->id,
                        'batch_id' => $batch->id,
                        'attempt' => 1,
                        'channel' => $data->channel->value,
                        'type' => $data->type->value,
                        'created_at' => now()->toISOString(),
                    ],
                    'status' => OutboxStatus::Pending,
                    'attempts' => 0,
                    'available_at' => now(),
                ]);
            }

            return $batch->load('notifications');
        });
    }
}
