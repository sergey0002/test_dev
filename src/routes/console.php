<?php

use App\Application\Actions\ConsumeNotificationMessageAction;
use App\Application\Actions\PublishOutboxMessagesAction;
use App\Infrastructure\RabbitMq\RabbitMqConnectionFactory;
use App\Infrastructure\RabbitMq\RabbitMqTopology;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Создает подписчиков для нагрузочного теста.
// Команда переиспользует upsert, чтобы повторный запуск был идемпотентным.
Artisan::command('loadtest:seed-subscribers {--count=50000}', function () {
    $count = max(1, (int) $this->option('count'));
    $now = now();
    $chunk = [];

    for ($i = 11; $i < 11 + $count; $i++) {
        $kind = $i % 20;
        $emailDomain = match (true) {
            $kind === 0 => 'temporary-error.test',
            $kind === 1 => 'invalid.test',
            default => 'load.test',
        };
        $phoneSuffix = match (true) {
            $kind === 2 => '000',
            $kind === 3 => '999',
            default => str_pad((string) ($i % 1000), 3, '1', STR_PAD_LEFT),
        };

        $chunk[] = [
            'id' => $i,
            'email' => "load{$i}@{$emailDomain}",
            'phone' => '+7900'.str_pad((string) $i, 7, '0', STR_PAD_LEFT).$phoneSuffix,
            'name' => "Load Subscriber {$i}",
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (count($chunk) >= 1000) {
            DB::table('subscribers')->upsert($chunk, ['id'], ['email', 'phone', 'name', 'is_active', 'updated_at']);
            $chunk = [];
        }
    }

    if ($chunk !== []) {
        DB::table('subscribers')->upsert($chunk, ['id'], ['email', 'phone', 'name', 'is_active', 'updated_at']);
    }

    $this->info("Созданы или обновлены loadtest-подписчики: {$count}");
})->purpose('Seed subscribers for load testing');

// Полный сброс данных нагрузочного контура.
// Флаг --with-subscribers также удаляет тестовых loadtest-подписчиков.
Artisan::command('loadtest:reset {--with-subscribers}', function () {
    DB::transaction(function () {
        DB::table('outbox_messages')->delete();
        DB::table('provider_attempts')->delete();
        DB::table('notification_events')->delete();
        DB::table('notifications')->delete();
        DB::table('notification_batches')->delete();
    });

    if ($this->option('with-subscribers')) {
        DB::table('subscribers')->where('id', '>', 10)->delete();
        $this->warn('Loadtest-подписчики с id > 10 удалены.');
    }

    $this->info('Loadtest-данные очищены.');
})->purpose('Reset loadtest batches, notifications, events, attempts and outbox');

// Диагностическая сводка по состоянию сервиса уведомлений.
// Используется для быстрой проверки после smoke и нагрузочных тестов.
Artisan::command('notifications:stats {--json}', function () {
    $notificationStatuses = DB::table('notifications')
        ->selectRaw('status, count(*) as total')
        ->groupBy('status')
        ->pluck('total', 'status')
        ->map(fn ($value) => (int) $value)
        ->all();

    $outboxStatuses = DB::table('outbox_messages')
        ->selectRaw('status, count(*) as total')
        ->groupBy('status')
        ->pluck('total', 'status')
        ->map(fn ($value) => (int) $value)
        ->all();

    $providerAttempts = DB::table('provider_attempts')
        ->selectRaw('result, count(*) as total')
        ->groupBy('result')
        ->pluck('total', 'result')
        ->map(fn ($value) => (int) $value)
        ->all();

    $stats = [
        'batches' => (int) DB::table('notification_batches')->count(),
        'subscribers' => (int) DB::table('subscribers')->count(),
        'notifications' => $notificationStatuses,
        'outbox' => $outboxStatuses,
        'provider_attempts' => $providerAttempts,
        'totals' => [
            'notifications' => (int) DB::table('notifications')->count(),
            'events' => (int) DB::table('notification_events')->count(),
            'outbox' => (int) DB::table('outbox_messages')->count(),
        ],
        'duplicates' => [
            'idempotency_keys' => (int) DB::query()
                ->fromSub(
                    DB::table('notification_batches')
                        ->select('idempotency_key')
                        ->groupBy('idempotency_key')
                        ->havingRaw('count(*) > 1'),
                    'duplicates',
                )
                ->count(),
            'notifications' => (int) DB::query()
                ->fromSub(
                    DB::table('notifications')
                        ->select('batch_id', 'subscriber_id', 'channel')
                        ->groupBy('batch_id', 'subscriber_id', 'channel')
                        ->havingRaw('count(*) > 1'),
                    'duplicates',
                )
                ->count(),
            'outbox' => (int) DB::query()
                ->fromSub(
                    DB::table('outbox_messages')
                        ->select('aggregate_type', 'aggregate_id', 'routing_key')
                        ->groupBy('aggregate_type', 'aggregate_id', 'routing_key')
                        ->havingRaw('count(*) > 1'),
                    'duplicates',
                )
                ->count(),
        ],
    ];

    if ($this->option('json')) {
        $this->line(json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return;
    }

    $this->info('Notification Service stats');
    $this->line('Batches: '.$stats['batches']);
    $this->line('Subscribers: '.$stats['subscribers']);
    $this->line('Notifications total: '.$stats['totals']['notifications']);
    $this->line('Events total: '.$stats['totals']['events']);
    $this->line('Outbox total: '.$stats['totals']['outbox']);
    $this->newLine();
    $this->line('Notifications by status: '.json_encode($stats['notifications'], JSON_UNESCAPED_UNICODE));
    $this->line('Outbox by status: '.json_encode($stats['outbox'], JSON_UNESCAPED_UNICODE));
    $this->line('Provider attempts: '.json_encode($stats['provider_attempts'], JSON_UNESCAPED_UNICODE));
    $this->line('Duplicates: '.json_encode($stats['duplicates'], JSON_UNESCAPED_UNICODE));
})->purpose('Show notification service counters for load tests');

// Объявляет topology в RabbitMQ.
// Команду полезно выполнять после старта стенда и после принудительного reset брокера.
Artisan::command('notifications:setup-broker', function () {
    $connection = app(RabbitMqConnectionFactory::class)->make();
    $channel = $connection->channel();

    try {
        app(RabbitMqTopology::class)->declare($channel);
        $this->info('RabbitMQ topology готова: exchange, high/normal, retry и DLQ очереди созданы.');
    } finally {
        $channel->close();
        $connection->close();
    }
})->purpose('Declare RabbitMQ exchange, queues, retry queues and DLQ');

// Публикует сообщения outbox в брокер.
// --loop включает непрерывный режим publisher; --once делает один проход.
Artisan::command('notifications:publish-outbox {--loop} {--once} {--limit=} {--sleep=}', function () {
    $action = app(PublishOutboxMessagesAction::class);
    $limit = max(
        1,
        (int) ($this->option('limit') ?? config('notifications.commands.outbox_limit', 100)),
    );
    $sleep = max(
        1,
        (int) ($this->option('sleep') ?? config('notifications.commands.outbox_sleep_seconds', 2)),
    );

    do {
        $published = $action->execute($limit);
        $this->info("Опубликовано outbox-сообщений: {$published}");

        if ($this->option('once')) {
            break;
        }

        if (! $this->option('loop')) {
            break;
        }

        sleep($sleep);
    } while (true);
})->purpose('Publish pending outbox messages to RabbitMQ');

// Читает сообщения из RabbitMQ.
// На каждое сообщение вызывается application-action, после чего идет ack/reject и фиксация результата обработки.
Artisan::command('notifications:consume {--queue=normal} {--once} {--limit=}', function () {
    $queueKey = (string) $this->option('queue');
    $queues = config('notifications.queues');
    $queue = match ($queueKey) {
        'high' => $queues['high'],
        'normal' => $queues['normal'],
        default => $queueKey,
    };
    $limit = max(
        0,
        (int) ($this->option('limit') ?? config('notifications.commands.consumer_limit', 0)),
    );
    $processed = 0;
    $waitTimeoutSeconds = max(1, (int) config('notifications.commands.consumer_wait_timeout_seconds', 5));

    $connection = app(RabbitMqConnectionFactory::class)->make();
    $channel = $connection->channel();

    try {
        app(RabbitMqTopology::class)->declare($channel);
        $channel->basic_qos(null, config('notifications.consumer_prefetch', 10), null);

        $this->info("Worker начал читать очередь {$queue}.");

        $channel->basic_consume(
            $queue,
            '',
            false,
            false,
            false,
            false,
            function ($message) use ($channel, &$processed, $limit) {
                try {
                    $payload = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);
                    app(ConsumeNotificationMessageAction::class)->execute($payload, $channel);
                    $message->ack();
                    $processed++;
                    $this->line("Сообщение обработано. Всего: {$processed}");
                } catch (\Throwable $exception) {
                    report($exception);
                    $message->reject(false);
                    $processed++;
                    $this->error('Ошибка обработки: '.$exception->getMessage());
                }

                if ($limit > 0 && $processed >= $limit) {
                    $channel->basic_cancel($message->getConsumerTag());
                }
            },
        );

        while ($channel->is_consuming()) {
            if ($this->option('once') && $processed > 0) {
                break;
            }

            try {
                $channel->wait(null, false, $waitTimeoutSeconds);
            } catch (\PhpAmqpLib\Exception\AMQPTimeoutException) {
                if ($this->option('once')) {
                    break;
                }
            }
        }
    } finally {
        $channel->close();
        $connection->close();
    }

    $this->info("Worker завершен. Обработано сообщений: {$processed}");
})->purpose('Consume notification messages from RabbitMQ queue');
