<?php

namespace Tests\Feature\Api;

use App\Domain\Notification\Enums\NotificationStatus;
use App\Domain\Notification\Enums\OutboxStatus;
use App\Models\NotificationBatch;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateNotificationBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_api_creates_batch_notifications_events_and_outbox(): void
    {
        $subscribers = Subscriber::factory()->count(3)->create();

        $response = $this->postJson('/api/v1/notifications/bulk', [
            'channel' => 'email',
            'type' => 'transactional',
            'message' => 'Ваш код доступа: 1234',
            'recipient_ids' => $subscribers->pluck('id')->all(),
            'idempotency_key' => 'auth-codes-001',
            'metadata' => ['source_service' => 'auth'],
        ]);

        $response->assertAccepted()
            ->assertJsonPath('data.status', 'accepted')
            ->assertJsonPath('data.requested_count', 3)
            ->assertJsonPath('data.accepted_count', 3);

        $batch = NotificationBatch::query()->firstOrFail();

        $this->assertDatabaseCount('notifications', 3);
        $this->assertDatabaseCount('notification_events', 3);
        $this->assertDatabaseCount('outbox_messages', 3);
        $this->assertDatabaseHas('notifications', [
            'batch_id' => $batch->id,
            'status' => NotificationStatus::Queued->value,
            'priority' => 100,
        ]);
        $this->assertDatabaseHas('outbox_messages', [
            'status' => OutboxStatus::Pending->value,
            'routing_key' => 'notification.high',
        ]);
    }

    public function test_same_idempotency_key_and_same_payload_returns_existing_batch(): void
    {
        $subscribers = Subscriber::factory()->count(2)->create();
        $payload = [
            'channel' => 'email',
            'type' => 'marketing',
            'message' => 'Акция',
            'recipient_ids' => $subscribers->pluck('id')->all(),
            'idempotency_key' => 'marketing-001',
            'metadata' => ['source_service' => 'crm'],
        ];

        $first = $this->postJson('/api/v1/notifications/bulk', $payload);
        $second = $this->postJson('/api/v1/notifications/bulk', $payload);

        $first->assertAccepted();
        $second->assertAccepted();
        $this->assertSame(
            $first->json('data.batch_id'),
            $second->json('data.batch_id'),
        );
        $this->assertDatabaseCount('notification_batches', 1);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseCount('outbox_messages', 2);
    }

    public function test_same_idempotency_key_with_different_payload_returns_conflict(): void
    {
        $subscriber = Subscriber::factory()->create();

        $this->postJson('/api/v1/notifications/bulk', [
            'channel' => 'email',
            'type' => 'transactional',
            'message' => 'Первое сообщение',
            'recipient_ids' => [$subscriber->id],
            'idempotency_key' => 'same-key',
        ])->assertAccepted();

        $this->postJson('/api/v1/notifications/bulk', [
            'channel' => 'email',
            'type' => 'transactional',
            'message' => 'Другое сообщение',
            'recipient_ids' => [$subscriber->id],
            'idempotency_key' => 'same-key',
        ])->assertConflict();
    }

    public function test_validation_errors_return_422(): void
    {
        $this->postJson('/api/v1/notifications/bulk', [
            'channel' => 'push',
            'type' => 'marketing',
            'message' => '',
            'recipient_ids' => [],
            'idempotency_key' => 'bad',
        ])->assertUnprocessable();
    }
}
