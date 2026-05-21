<?php

namespace Tests\Unit\Domain;

use App\Domain\Notification\Services\NotificationPayloadHasher;
use Tests\TestCase;

class NotificationPayloadHasherTest extends TestCase
{
    public function test_hash_is_stable_for_same_payload_with_different_recipient_order(): void
    {
        $hasher = new NotificationPayloadHasher();

        $first = $hasher->hash([
            'channel' => 'email',
            'type' => 'transactional',
            'message' => 'Код 1234',
            'recipient_ids' => [3, 1, 2],
            'idempotency_key' => ' key ',
            'metadata' => ['b' => 2, 'a' => 1],
        ]);

        $second = $hasher->hash([
            'metadata' => ['a' => 1, 'b' => 2],
            'idempotency_key' => 'key',
            'recipient_ids' => [1, 2, 3],
            'message' => 'Код 1234',
            'type' => 'transactional',
            'channel' => 'email',
        ]);

        $this->assertSame($first, $second);
    }

    public function test_hash_changes_when_message_changes(): void
    {
        $hasher = new NotificationPayloadHasher();

        $first = $hasher->hash(['message' => 'one', 'recipient_ids' => [1]]);
        $second = $hasher->hash(['message' => 'two', 'recipient_ids' => [1]]);

        $this->assertNotSame($first, $second);
    }
}
