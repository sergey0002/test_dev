<?php

namespace App\Infrastructure\RabbitMq;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMqConnectionFactory
{
    /**
     * Создает AMQP-подключение к RabbitMQ по параметрам из конфигурации.
     * Так мы не привязываем код к env-переменным напрямую и держим единый источник настроек.
     */
    public function make(): AMQPStreamConnection
    {
        $broker = config('notifications.broker');

        return new AMQPStreamConnection(
            host: (string) ($broker['host'] ?? 'rabbitmq'),
            port: (int) ($broker['port'] ?? 5672),
            user: (string) ($broker['user'] ?? 'guest'),
            password: (string) ($broker['password'] ?? 'guest'),
            vhost: (string) ($broker['vhost'] ?? '/'),
            heartbeat: (int) ($broker['heartbeat'] ?? 30),
        );
    }
}
