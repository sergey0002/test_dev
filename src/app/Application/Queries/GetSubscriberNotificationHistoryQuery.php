<?php

namespace App\Application\Queries;

use App\Models\Subscriber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetSubscriberNotificationHistoryQuery
{
    public function execute(Subscriber $subscriber, array $filters = []): LengthAwarePaginator
    {
        $query = $subscriber->notifications()
            ->with(['events' => fn ($query) => $query->orderBy('created_at')])
            ->latest('created_at');

        foreach (['channel', 'status'] as $filter) {
            if (! empty($filters[$filter])) {
                $query->where($filter, $filters[$filter]);
            }
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        return $query->paginate(
            perPage: min((int) ($filters['limit'] ?? 20), 100),
            page: (int) ($filters['page'] ?? 1),
        );
    }
}
