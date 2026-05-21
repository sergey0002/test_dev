<?php

namespace Tests\Unit\Domain;

use App\Domain\Notification\Enums\NotificationType;
use App\Domain\Notification\Services\RoutingKeyResolver;
use Tests\TestCase;

class RoutingKeyResolverTest extends TestCase
{
    public function test_resolves_exchange_routing_key_and_priority(): void
    {
        $resolver = new RoutingKeyResolver();

        $this->assertSame('notifications.direct', $resolver->exchange());
        $this->assertSame('notification.high', $resolver->routingKey(NotificationType::Transactional));
        $this->assertSame(100, $resolver->priority(NotificationType::Transactional));
        $this->assertSame('notification.normal', $resolver->routingKey(NotificationType::Marketing));
        $this->assertSame(10, $resolver->priority(NotificationType::Marketing));
    }
}
