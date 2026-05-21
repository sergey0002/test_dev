<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_id' => $this->batch_id,
            'subscriber_id' => $this->subscriber_id,
            'channel' => $this->channel->value,
            'type' => $this->type->value,
            'message' => $this->message_snapshot,
            'status' => $this->status->value,
            'attempts_count' => $this->attempts_count,
            'queued_at' => $this->queued_at?->toISOString(),
            'sent_at' => $this->sent_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'dropped_at' => $this->dropped_at?->toISOString(),
            'events' => NotificationEventResource::collection($this->whenLoaded('events')),
        ];
    }
}
