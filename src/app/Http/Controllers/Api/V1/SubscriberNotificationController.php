<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Queries\GetSubscriberNotificationHistoryQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriberNotificationController extends Controller
{
    public function index(
        Subscriber $subscriber,
        Request $request,
        GetSubscriberNotificationHistoryQuery $query,
    ): AnonymousResourceCollection {
        // Query-параметры фильтруют историю, а сама сборка данных остается в query-объекте.
        $notifications = $query->execute($subscriber, $request->only([
            'limit',
            'page',
            'channel',
            'status',
            'from',
            'to',
        ]));

        return NotificationResource::collection($notifications);
    }
}
