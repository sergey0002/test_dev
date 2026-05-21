<?php

namespace App\Infrastructure\RabbitMq;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMqTopology
{
    public function declare(AMQPChannel $channel): void
    {
        $exchange = config('notifications.exchange');
        $queues = config('notifications.queues');
        $routing = config('notifications.routing');
        $retryDelayMs = $this->retryDelayMs();

        // Durable direct exchange для основных сообщений, retry и DLQ.
        // durable=true гарантирует, что topology сохраняется после перезапуска брокера.
        $channel->exchange_declare($exchange, 'direct', false, true, false);

        $this->declareMainQueue($channel, $queues['high'], $exchange, $routing['high']);
        $this->declareMainQueue($channel, $queues['normal'], $exchange, $routing['normal']);

        $this->declareRetryQueue(
            $channel,
            $queues['retry_high'],
            $exchange,
            $routing['retry_high'],
            $routing['high'],
            $retryDelayMs,
        );
        $this->declareRetryQueue(
            $channel,
            $queues['retry_normal'],
            $exchange,
            $routing['retry_normal'],
            $routing['normal'],
            $retryDelayMs,
        );

        $channel->queue_declare($queues['dlq'], false, true, false, false);
        $channel->queue_bind($queues['dlq'], $exchange, $routing['dlq']);
    }

    private function declareMainQueue(AMQPChannel $channel, string $queue, string $exchange, string $routingKey): void
    {
        $channel->queue_declare($queue, false, true, false, false);
        $channel->queue_bind($queue, $exchange, $routingKey);
    }

    private function declareRetryQueue(
        AMQPChannel $channel,
        string $queue,
        string $exchange,
        string $routingKey,
        string $deadLetterRoutingKey,
        int $retryDelayMs,
    ): void {
        // Retry-очереди держат сообщения по TTL, после чего возвращают их
        // в основную очередь через dead-letter routing.
        $channel->queue_declare($queue, false, true, false, false, false, new AMQPTable([
            'x-message-ttl' => $retryDelayMs,
            'x-dead-letter-exchange' => $exchange,
            'x-dead-letter-routing-key' => $deadLetterRoutingKey,
        ]));
        $channel->queue_bind($queue, $exchange, $routingKey);
    }

    private function retryDelayMs(): int
    {
        $delays = config('notifications.retry_delays', [10]);
        $firstDelaySeconds = max(1, (int) ($delays[0] ?? 10));

        return $firstDelaySeconds * 1000;
    }
}
