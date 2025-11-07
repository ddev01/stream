<?php

use App\Http\Controllers\Api\SubscriptionHistoryController;
use App\Http\Controllers\Api\TwitchStatsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Twitch data ingestion endpoints (protected by API token or dev key)
Route::post('/twitch/stats', [TwitchStatsController::class, 'bulkImport'])->middleware('dev.api');
Route::post('/twitch/subscription-history', [SubscriptionHistoryController::class, 'bulkImport'])->middleware('dev.api');
