<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\HealthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', HealthController::class);
