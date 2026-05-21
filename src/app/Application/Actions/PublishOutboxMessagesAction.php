<?php

namespace App\Application\Actions;

use App\Domain\Notification\Enums\OutboxStatus;
use App\Infrastructure\RabbitMq\RabbitMqConnectionFactory;
use App\Infrastructure\RabbitMq\RabbitMqPublisher;
use App\Models\OutboxMessage;
use Throwable;

class PublishOutboxMessagesAction
{
    public function __construct(
        private readonly RabbitMqConnectionFactory $connections,
        private readonly RabbitMqPublisher $publisher,
    ) {
    }

    public function execute(int $limit = 100): int
    {
        $connection = $this->connections->make();
        $channel = $connection->channel();
        $published = 0;

        try {
            $messages = OutboxMessage::query()
                ->whereIn('status', [OutboxStatus::Pending->value, OutboxStatus::Failed->value])
                ->where('available_at', '<=', now())
                ->orderBy('id')
                ->limit($limit)
                ->get();

            foreach ($messages as $message) {
                try {
                    // Публикуем только то, что уже гарантированно сохранено в outbox-таблице.
                    // Это и есть ключевой принцип transactional outbox.
                    $this->publisher->publish(
                        $channel,
                        $message->exchange,
                        $message->routing_key,
                        $message->payload,
                    );

                    $message->markPublished();
                    $published++;
                } catch (Throwable $exception) {
                    // Ошибка публикации не теряет сообщение: помечаем failed и переносим в retry по backoff.
                    $message->markFailed(
                        mb_substr($exception->getMessage(), 0, 1000),
                        now()->addSeconds($this->nextOutboxDelay($message->attempts + 1)),
                    );
                }
            }
        } finally {
            $channel->close();
            $connection->close();
        }

        return $published;
    }

    private function nextOutboxDelay(int $attempt): int
    {
        $delays = config('notifications.retry_delays', [10, 30, 120]);

        return (int) ($delays[min($attempt - 1, count($delays) - 1)] ?? 120);
    }
}
