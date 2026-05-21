# Notification Service

Тестовое задание на позицию PHP Backend Developer.

Проект реализует микросервис уведомлений на `Laravel 11 + PostgreSQL + Redis + RabbitMQ`, умеет принимать массовые запросы на отправку уведомлений, раскладывать их на отдельные записи, публиковать задачи через `outbox` и RabbitMQ, хранить историю и текущий статус уведомлений, а также запускать нагрузочные тесты через `k6 + InfluxDB + Grafana`.

## Что реализовано

- Bulk API: `POST /api/v1/notifications/bulk`
- История уведомлений подписчика: `GET /api/v1/subscribers/{id}/notifications`
- Сводка по батчу: `GET /api/v1/batches/{id}`
- Health-check: `GET /health`
- Идемпотентность через `idempotency_key`
- PostgreSQL-модель батчей, уведомлений, событий, outbox и provider attempts
- RabbitMQ topology и artisan-команды для publisher/consumer
- Load testing стек: `k6 + InfluxDB + Grafana`

## Стек

- `PHP 8.3`
- `Laravel 11`
- `PostgreSQL 16`
- `Redis 7`
- `RabbitMQ 3-management`
- `Docker Compose`
- `k6`
- `InfluxDB 1.8`
- `Grafana 10`

## Архитектура

- `nginx` принимает HTTP-запросы
- `app` исполняет Laravel API
- `postgres` хранит основную модель данных
- `redis` используется для вспомогательных задач и health-check
- `rabbitmq` хранит очереди уведомлений
- `worker-high` читает high-priority очередь
- `worker-normal` читает normal-priority очередь
- `outbox-publisher` публикует pending outbox-сообщения в RabbitMQ

Для надежности используется `transactional outbox`: API сначала фиксирует batch, notifications и outbox-записи в PostgreSQL, а уже потом отдельный publisher публикует их в брокер.
 
 
## Быстрый старт

### Windows

```bat
deploy.bat
``` 
## Основные URL и креды

### Приложение

- API: `http://localhost:18080`
- Health: `http://localhost:18080/health`

### Grafana
![Grafana Dashboard](./grafana.jpg)
- URL: `http://localhost:3000`
- Login: `admin`
- Password: `admin`
- Dashboard: `http://localhost:3000/d/notification-service-k6/notification-service-k6-load-test`
 

### RabbitMQ

- Management UI: `http://localhost:15672`
- Login: `guest`
- Password: `guest`
- AMQP host from host machine: `localhost`
- AMQP port: `5672`

### PostgreSQL

- Host: `localhost`
- Port: `5432`
- Database: `notification_service`
- User: `notification`
- Password: `notification`
- DSN: `postgresql://notification:notification@localhost:5432/notification_service`

### Redis (для тротлинга при превышении нагрузки в енв можно указать сколько запрсов в секунду готовы обработать)

- Host: `localhost`
- Port: `6379`
- Password: НЕТ
- URL: `redis://localhost:6379`

### InfluxDB

- Health URL: `http://localhost:8086/ping`
- Database: `k6`
- Auth: disabled for local environment

Примечание: `http://localhost:8086/` может отвечать `404`, это нормально для `InfluxDB 1.8`. Для проверки использовать `/ping`.
 
 
## API

### OpenAPI / Postman

- OpenAPI спецификация: [openapi.yaml](./openapi.yaml)
- Postman коллекция: [postman_collection.json](./postman_collection.json)
 

### 1. Создать batch уведомлений

`POST /api/v1/notifications/bulk`

Пример запроса:

```json
{
  "channel": "email",
  "type": "transactional",
  "message": "Ваш код доступа: 1234",
  "recipient_ids": [1, 2, 3],
  "idempotency_key": "auth-codes-001",
  "metadata": {
    "source_service": "auth"
  }
}
```

Пример `curl`:

```bash
curl -X POST http://localhost:18080/api/v1/notifications/bulk \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"channel\":\"email\",\"type\":\"transactional\",\"message\":\"Ваш код доступа: 1234\",\"recipient_ids\":[1,2,3],\"idempotency_key\":\"auth-codes-001\",\"metadata\":{\"source_service\":\"auth\"}}"
```

Ожидаемый ответ: `202 Accepted`

### 2. Получить сводку по batch

`GET /api/v1/batches/{id}`

Пример:

```bash
curl http://localhost:18080/api/v1/batches/1
```

### 3. Получить историю уведомлений подписчика

`GET /api/v1/subscribers/{id}/notifications`

Пример:

```bash
curl http://localhost:18080/api/v1/subscribers/1/notifications
```

### 4. Проверить состояние сервиса

`GET /health`

Пример:

```bash
curl http://localhost:18080/health
```

## Сценарий троттлинга bulk API

Для `POST /api/v1/notifications/bulk` включен rate limit через middleware `throttle`.
Лимит задается в env:

- `NOTIFICATION_API_BULK_THROTTLE_ATTEMPTS` (по умолчанию `60`)
- `NOTIFICATION_API_BULK_THROTTLE_DECAY_MINUTES` (по умолчанию `1`)

Что происходит при превышении лимита:

- клиент получает `HTTP 429 Too Many Requests`;
- в headers приходят `Retry-After`, `X-RateLimit-Limit`, `X-RateLimit-Remaining`;
- запрос режется на уровне middleware, до бизнес-логики (batch/notifications/outbox не создаются).

Пример ответа (prod, `APP_DEBUG=false`):

```json
{
  "message": "Too Many Attempts."
}
```
   

Для наблюдения за очередями открыть:

- `http://localhost:15672`
- логин `guest`
- пароль `guest`


МОЖНО НАБЛЮДАТЬ:

- `Queues and Streams`
- `Ready`
- `Unacked`
- `Publish rate`
- `Ack rate`

## Нагрузочное тестирование

### Что используется

- `k6` генерирует HTTP-нагрузку
- `InfluxDB` хранит метрики
- `Grafana` ДЛЯ ВИЗУАЛИЗАЦИИ  

### Поднять только loadtest-контур

```bash
docker compose -f loadtest/docker-compose.loadtest.yml up -d influxdb grafana
```

### Подготовить тестовых подписчиков

Минимальный набор:

```bash
docker compose exec -T app php artisan loadtest:seed-subscribers --count=100
```

Побольше для нагрузки:

```bash
docker compose exec -T app php artisan loadtest:seed-subscribers --count=1000
```

Loadtest-подписчики создаются с `id` начиная с `11`.

### Запуск нагрузочного сценария через helper (НА ВИНДЕ)

```bat
loadtest\scripts\loadtest.bat soak
```
   

## Тесты

```bash
docker compose exec -T app php artisan test
```

Актуальный перечень автотестов:

- `src/tests/Feature/Api/CreateNotificationBatchTest.php` — проверяет `POST /api/v1/notifications/bulk`: создание `batch/notifications/events/outbox`, идемпотентный повтор с тем же payload, конфликт при другом payload и валидацию `422`.
- `src/tests/Feature/Api/HealthTest.php` — проверяет `GET /health` и статус зависимостей `database/redis/rabbitmq`.
- `src/tests/Feature/Api/NotificationReadApiTest.php` — проверяет read API: историю уведомлений подписчика с событиями и summary по batch со счетчиками статусов.
- `src/tests/Unit/Domain/NotificationPayloadHasherTest.php` — проверяет хеширование payload для идемпотентности: одинаковый хеш при перестановке полей/ID и изменение хеша при изменении данных.
- `src/tests/Unit/Domain/RoutingKeyResolverTest.php` — проверяет вычисление `exchange/routing_key/priority` для публикации в RabbitMQ.
- `src/tests/Unit/Domain/StatusTransitionServiceTest.php` — проверяет переходы статусов: запись event, фиксацию времени и запрет невалидного перехода из финального статуса.

Актуальный нагрузочный тест (`k6`):

- `loadtest/k6/scenarios/07_soak.js` — основной soak-сценарий длительной смешанной нагрузки.
 
