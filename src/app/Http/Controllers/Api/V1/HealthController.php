<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthController extends Controller
{
    /**
     * Возвращает агрегированный health-статус и статус зависимостей.
     * Нужен для мониторинга и быстрой проверки живости сервиса.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'rabbitmq' => $this->checkRabbitMq(),
        ];

        $ok = collect($checks)->every(fn (string $status): bool => $status === 'ok');

        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $ok ? 200 : 503);
    }

    private function checkDatabase(): string
    {
        try {
            DB::select('select 1');

            return 'ok';
        } catch (Throwable) {
            return 'failed';
        }
    }

    private function checkRedis(): string
    {
        try {
            Redis::connection()->ping();

            return 'ok';
        } catch (Throwable) {
            return 'failed';
        }
    }

    private function checkRabbitMq(): string
    {
        $broker = config('notifications.broker');
        $host = (string) ($broker['host'] ?? 'rabbitmq');
        $port = (int) ($broker['port'] ?? 5672);
        $timeout = (float) config('notifications.health.rabbitmq_ping_timeout_seconds', 2);

        try {
            // Делаем TCP-проверку порта брокера как дешевый AMQP smoke-check.
            // Для health-эндпоинта этого достаточно и не создает лишнюю нагрузку.
            $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);

            if ($socket === false) {
                return 'failed';
            }

            fclose($socket);

            return 'ok';
        } catch (Throwable) {
            return 'failed';
        }
    }
}
