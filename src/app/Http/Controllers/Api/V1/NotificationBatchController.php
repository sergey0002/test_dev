<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Actions\CreateNotificationBatchAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNotificationBatchRequest;
use App\Http\Resources\NotificationBatchResource;
use Illuminate\Http\JsonResponse;

class NotificationBatchController extends Controller
{
    public function store(StoreNotificationBatchRequest $request, CreateNotificationBatchAction $action): JsonResponse
    {
        // Контроллер только валидирует вход и делегирует бизнес-логику в application-слой.
        $batch = $action->execute($request->toData());

        return (new NotificationBatchResource($batch))
            ->response()
            ->setStatusCode(202);
    }
}
