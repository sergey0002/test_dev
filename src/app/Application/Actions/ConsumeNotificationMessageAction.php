<?php

namespace App\Application\Actions;

use App\Domain\Notification\Enums\NotificationStatus;
use App\Domain\Notification\Enums\ProviderAttemptResult;
use App\Domain\Notification\Providers\MockNotificationProvider;
use App\Domain\Notification\Services\StatusTransitionService;
use App\Infrastructure\RabbitMq\RabbitMqPublisher;
use App\Models\Notification;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PhpAmqpLib\Channel\AMQPChannel;

class ConsumeNotificationMessageAction
{
    public function __construct(
        private readonly MockNotificationProvider $provider,
        private readonly StatusTransitionService $statuses,
        private readonly RabbitMqPublisher $publisher,
    ) {
    }

    public function execute(array $payload, AMQPChannel $channel): void
    {
        $notificationId = (int) ($payload['notification_id'] ?? 0);

        if ($notificationId <= 0) {
            // Некорректное сообщение не должно блокировать обработку очереди.
            $this->publishToDlq($channel, $payload + ['error' => 'missing_notification_id']);

            return;
        }

        // Для notification_id берем lock, чтобы исключить конкурентную обработку одной записи.
        $lock = Cache::lock('notification:consume:'.$notificationId, config('notifications.lock_ttl', 60));
        $lockBlockSeconds = (int) config('notifications.commands.lock_block_seconds', 5);

        try {
            $lock->block($lockBlockSeconds, function () use ($notificationId, $payload, $channel) {
                DB::transaction(function () use ($notificationId, $payload, $channel) {
                    $notification = Notification::query()
                        ->with('subscriber')
                        ->lockForUpdate()
                        ->find($notificationId);

                    if ($notification === null) {
                        // Доставлять нечего: исходная запись отсутствует, отправляем в DLQ для анализа.
                        $this->publishToDlq($channel, $payload + ['error' => 'notification_not_found']);

                        return;
                    }

                    if ($notification->isFinal()) {
                        // Для финального статуса повторная доставка не нужна (idempotent no-op).
                        return;
                    }

                    $attemptNo = $notification->attempts_count + 1;
                    $providerResult = $this->provider->send($notification);

                    $notification->providerAttempts()->create([
                        'attempt_no' => $attemptNo,
                        'provider' => 'mock_'.$notification->channel->value,
                        'result' => $providerResult->result,
                        'provider_message_id' => $providerResult->providerMessageId,
                        'error_code' => $providerResult->errorCode,
                        'error_message' => $providerResult->errorMessage,
                        'duration_ms' => $providerResult->durationMs,
                    ]);

                    $notification->forceFill([
                        'attempts_count' => $attemptNo,
                        'last_error_code' => $providerResult->errorCode,
                        'last_error_message' => $providerResult->errorMessage,
                    ])->save();

                    match ($providerResult->result) {
                        ProviderAttemptResult::Success => $this->markDelivered($notification, $providerResult->providerMessageId),
                        ProviderAttemptResult::PermanentError => $this->drop($notification, 'provider_permanent_error', $providerResult->errorCode),
                        ProviderAttemptResult::TemporaryError => $this->retryOrDrop($notification, $payload, $channel, $attemptNo, $providerResult->errorCode),
                    };
                });
            });
        } catch (LockTimeoutException) {
            // Lock занят: возвращаем сообщение в retry, чтобы не терять обработку.
            $this->publishToRetry($channel, $payload);
        }
    }

    private function markDelivered(Notification $notification, ?string $providerMessageId): void
    {
        $notification->forceFill([
            'provider_message_id' => $providerMessageId,
            'last_error_code' => null,
            'last_error_message' => null,
        ])->save();

        $this->statuses->transition($notification, NotificationStatus::Sent, 'provider_accepted', [
            'provider_message_id' => $providerMessageId,
        ]);
        $this->statuses->transition($notification, NotificationStatus::Delivered, 'mock_provider_delivered', [
            'provider_message_id' => $providerMessageId,
        ]);
    }

    private function retryOrDrop(
        Notification $notification,
        array $payload,
        AMQPChannel $channel,
        int $attemptNo,
        ?string $errorCode,
    ): void {
        if ($attemptNo >= $notification->max_attempts) {
            $this->drop($notification, 'retry_limit_exceeded', $errorCode);
            // Лимит попыток исчерпан: фиксируем отказ и отправляем исходное сообщение в DLQ.
            $this->publishToDlq($channel, $payload + [
                'attempt' => $attemptNo,
                'error' => $errorCode ?? 'temporary_error_limit',
            ]);

            return;
        }

        $this->statuses->transition($notification, NotificationStatus::Queued, 'retry_scheduled', [
            'attempt' => $attemptNo,
            'error' => $errorCode,
        ]);

        $this->publishToRetry($channel, $payload + ['attempt' => $attemptNo + 1]);
    }

    private function drop(Notification $notification, string $reason, ?string $errorCode): void
    {
        $this->statuses->transition($notification, NotificationStatus::Dropped, $reason, [
            'error' => $errorCode,
        ]);
    }

    private function publishToRetry(AMQPChannel $channel, array $payload): void
    {
        $type = (string) ($payload['type'] ?? 'marketing');
        // Для retry сохраняем исходный класс приоритета (high/normal), чтобы не ломать SLA.
        $routingKey = $type === 'transactional'
            ? config('notifications.routing.retry_high')
            : config('notifications.routing.retry_normal');

        $this->publisher->publish($channel, config('notifications.exchange'), $routingKey, $payload);
    }

    private function publishToDlq(AMQPChannel $channel, array $payload): void
    {
        $this->publisher->publish($channel, config('notifications.exchange'), config('notifications.routing.dlq'), $payload);
    }
}
