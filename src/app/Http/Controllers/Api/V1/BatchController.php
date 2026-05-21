<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Queries\GetBatchSummaryQuery;
use App\Http\Controllers\Controller;
use App\Http\Resources\BatchSummaryResource;
use App\Models\NotificationBatch;
use Illuminate\Http\JsonResponse;

class BatchController extends Controller
{
    public function show(NotificationBatch $batch, GetBatchSummaryQuery $query): JsonResponse
    {
        return (new BatchSummaryResource($query->execute($batch)))->response();
    }
}
