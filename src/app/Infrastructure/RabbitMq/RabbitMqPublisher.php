<?php

namespace App\Infrastructure\RabbitMq;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMqPublisher
{
    public function __construct(private readonly RabbitMqTopology $topology)
    {
    }

    public function publish(AMQPChannel $channel, string $exchange, string $routingKey, array $payload): void
    {
        // Идемпотентный declare позволяет publisher/consumer стартовать в любом порядке:
        // перед каждой publish-операцией гарантируем существование exchange/queue/binding.
        $this->topology->declare($channel);

        $message = new AMQPMessage(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'timestamp' => time(),
        ]);

        $channel->basic_publish($message, $exchange, $routingKey);
    }
}
