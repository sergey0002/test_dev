<?php

namespace Tests\Unit\Domain;

use App\Domain\Notification\Enums\NotificationStatus;
use App\Domain\Notification\Exceptions\InvalidStatusTransitionException;
use App\Domain\Notification\Services\StatusTransitionService;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusTransitionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_transitions_status_and_writes_event(): void
    {
        $notification = Notification::factory()->create();

        app(StatusTransitionService::class)->transition(
            $notification,
            NotificationStatus::Sent,
            'provider_accepted',
            ['provider' => 'mock_email'],
        );

        $notification->refresh();

        $this->assertSame(NotificationStatus::Sent, $notification->status);
        $this->assertNotNull($notification->sent_at);
        $this->assertDatabaseHas('notification_events', [
            'notification_id' => $notification->id,
            'from_status' => NotificationStatus::Queued->value,
            'to_status' => NotificationStatus::Sent->value,
            'reason' => 'provider_accepted',
        ]);
    }

    public function test_it_rejects_invalid_transition_from_final_status(): void
    {
        $notification = Notification::factory()->create([
            'status' => NotificationStatus::Delivered,
            'delivered_at' => now(),
        ]);

        $this->expectException(InvalidStatusTransitionException::class);

        app(StatusTransitionService::class)->transition($notification, NotificationStatus::Queued, 'bad_retry');
    }
}
