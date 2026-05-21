<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'batch_id' => $this->id,
            'status' => $this->status->value,
            'channel' => $this->channel->value,
            'type' => $this->type->value,
            'requested_count' => $this->requested_count,
            'accepted_count' => $this->accepted_count,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
