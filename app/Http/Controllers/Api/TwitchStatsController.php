<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BulkImportStatsRequest;
use App\Jobs\ImportTwitchStats;
use Illuminate\Http\JsonResponse;

/**
 * Handles bulk import of Twitch user stats from external sources
 */
class TwitchStatsController extends Controller
{
    /**
     * Bulk import/update Twitch user stats (queued for async processing)
     */
    public function bulkImport(BulkImportStatsRequest $request): JsonResponse
    {
        $stats = $request->validated()['stats'];

        // Dispatch job to queue for async processing
        ImportTwitchStats::dispatch($stats);

        // Return immediate response (202 Accepted)
        return response()->json([
            'status' => 'queued',
            'message' => 'Twitch stats import queued for processing',
            'records' => count($stats),
        ], 202);
    }
}
