<?php

use App\Http\Controllers\Api\TwitchStatsController;
use App\Http\Controllers\Api\TwitchUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Twitch data ingestion endpoints
Route::post('/twitch/users', [TwitchUserController::class, 'bulkImport']);
Route::post('/twitch/stats', [TwitchStatsController::class, 'bulkImport']);

// Simple endpoint to receive data from external applications (legacy)
Route::post('/receive-data', function (Request $request) {
    Log::info('Received Data:', [
        'timestamp' => now(),
        'headers' => $request->headers->all(),
        'data' => $request->all(),
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Data received successfully',
        'timestamp' => now()->toISOString(),
    ]);
});
