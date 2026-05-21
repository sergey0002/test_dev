<?php

namespace Tests\Feature\Api;

use App\Domain\Notification\Enums\NotificationStatus;
use App\Models\Notification;
use App\Models\NotificationBatch;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationReadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscriber_history_returns_notifications_with_events(): void
    {
        $subscriber = Subscriber::factory()->create();
        $notification = Notification::factory()->create(['subscriber_id' => $subscriber->id]);
        $notification->events()->create([
            'from_status' => null,
            'to_status' => NotificationStatus::Queued,
            'reason' => 'created',
            'meta' => [],
        ]);

        $this->getJson("/api/v1/subscribers/{$subscriber->id}/notifications")
            ->assertOk()
            ->assertJsonPath('data.0.id', $notification->id)
            ->assertJsonPath('data.0.status', 'queued')
            ->assertJsonPath('data.0.events.0.to_status', 'queued');
    }

    public function test_batch_summary_returns_status_counts(): void
    {
        $batch = NotificationBatch::factory()->create();
        Notification::factory()->count(2)->create([
            'batch_id' => $batch->id,
            'status' => NotificationStatus::Queued,
        ]);
        Notification::factory()->create([
            'batch_id' => $batch->id,
            'status' => NotificationStatus::Delivered,
            'delivered_at' => now(),
        ]);

        $this->getJson("/api/v1/batches/{$batch->id}")
            ->assertOk()
            ->assertJsonPath('data.batch_id', $batch->id)
            ->assertJsonPath('data.queued_count', 2)
            ->assertJsonPath('data.delivered_count', 1);
    }
}
