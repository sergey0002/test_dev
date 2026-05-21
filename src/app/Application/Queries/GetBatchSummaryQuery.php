<?php

namespace App\Application\Queries;

use App\Domain\Notification\Enums\NotificationStatus;
use App\Models\NotificationBatch;

class GetBatchSummaryQuery
{
    public function execute(NotificationBatch $batch): array
    {
        $counts = $batch->notifications()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'batch_id' => $batch->id,
            'requested_count' => $batch->requested_count,
            'accepted_count' => $batch->accepted_count,
            'queued_count' => (int) ($counts[NotificationStatus::Queued->value] ?? 0),
            'sent_count' => (int) ($counts[NotificationStatus::Sent->value] ?? 0),
            'delivered_count' => (int) ($counts[NotificationStatus::Delivered->value] ?? 0),
            'dropped_count' => (int) ($counts[NotificationStatus::Dropped->value] ?? 0),
        ];
    }
}
