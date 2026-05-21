<?php

use App\Http\Controllers\Api\V1\BatchController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\NotificationBatchController;
use App\Http\Controllers\Api\V1\SubscriberNotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

// Лимит берется из env через config/notifications.php.
// Это позволяет менять порог без правки кода и быстро подстраивать его под нагрузку.
$bulkThrottle = sprintf(
    'throttle:%d,%d',
    max(1, (int) config('notifications.api.bulk_throttle_attempts', 60)),
    max(1, (int) config('notifications.api.bulk_throttle_decay_minutes', 1)),
);

Route::prefix('v1')->group(function () use ($bulkThrottle) {
    Route::get('/health', HealthController::class);
    Route::post('/notifications/bulk', [NotificationBatchController::class, 'store'])->middleware($bulkThrottle);
    Route::get('/subscribers/{subscriber}/notifications', [SubscriberNotificationController::class, 'index']);
    Route::get('/batches/{batch}', [BatchController::class, 'show']);
});
