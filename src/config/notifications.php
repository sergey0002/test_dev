<?php

return [
    // Параметры подключения к RabbitMQ.
    // Вынесены в env, чтобы одинаково удобно работать локально, в CI и в Docker.
    'broker' => [
        'host' => env('RABBITMQ_HOST', 'rabbitmq'),
        'port' => (int) env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
        'heartbeat' => (int) env('RABBITMQ_HEARTBEAT', 30),
    ],

    // Главный exchange для маршрутизации всех сообщений сервиса.
    'exchange' => env('RABBITMQ_EXCHANGE', 'notifications.direct'),

    // Имена очередей.
    // Есть high/normal, чтобы транзакционные уведомления не стояли в очереди с маркетинговыми.
    'queues' => [
        'high' => env('RABBITMQ_QUEUE_HIGH', 'notifications.high'),
        'normal' => env('RABBITMQ_QUEUE_NORMAL', 'notifications.normal'),
        'retry_high' => env('RABBITMQ_QUEUE_RETRY_HIGH', 'notifications.high.retry'),
        'retry_normal' => env('RABBITMQ_QUEUE_RETRY_NORMAL', 'notifications.normal.retry'),
        'dlq' => env('RABBITMQ_QUEUE_DLQ', 'notifications.dlq'),
    ],

    // Routing keys для маршрутизации сообщений в соответствующие очереди.
    'routing' => [
        'high' => 'notification.high',
        'normal' => 'notification.normal',
        'retry_high' => 'notification.high.retry',
        'retry_normal' => 'notification.normal.retry',
        'dlq' => 'notification.dlq',
    ],

    // Троттлинг для POST /api/v1/notifications/bulk.
    // Ограничивает частоту запросов и защищает API от перегруза.
    'api' => [
        'bulk_throttle_attempts' => (int) env('NOTIFICATION_API_BULK_THROTTLE_ATTEMPTS', 60),
        'bulk_throttle_decay_minutes' => (int) env('NOTIFICATION_API_BULK_THROTTLE_DECAY_MINUTES', 1),
    ],

    // Задержки повторных попыток (в секундах).
    // Первый элемент используется как TTL для retry-очередей.
    'retry_delays' => array_map('intval', explode(',', env('NOTIFICATION_RETRY_DELAYS', '10,30,120'))),

    // Основные ограничения обработки уведомлений.
    'max_attempts' => (int) env('NOTIFICATION_MAX_ATTEMPTS', 3),
    'outbox_batch_size' => (int) env('NOTIFICATION_OUTBOX_BATCH_SIZE', 100),
    'lock_ttl' => (int) env('NOTIFICATION_LOCK_TTL', 60),
    'consumer_prefetch' => (int) env('NOTIFICATION_CONSUMER_PREFETCH', 10),

    // Параметры artisan-команд для publisher/consumer.
    'commands' => [
        'outbox_limit' => (int) env('NOTIFICATION_COMMAND_OUTBOX_LIMIT', 100),
        'outbox_sleep_seconds' => (int) env('NOTIFICATION_COMMAND_OUTBOX_SLEEP_SECONDS', 2),
        'consumer_limit' => (int) env('NOTIFICATION_COMMAND_CONSUMER_LIMIT', 0),
        'consumer_wait_timeout_seconds' => (int) env('NOTIFICATION_COMMAND_CONSUMER_WAIT_TIMEOUT_SECONDS', 5),
        'lock_block_seconds' => (int) env('NOTIFICATION_COMMAND_LOCK_BLOCK_SECONDS', 5),
    ],

    // Параметры health-check для проверки RabbitMQ.
    'health' => [
        'rabbitmq_ping_timeout_seconds' => (float) env('NOTIFICATION_HEALTH_RABBITMQ_TIMEOUT_SECONDS', 2),
    ],
];
