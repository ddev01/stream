<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BulkImportStatsRequest;
use App\Services\TwitchStatsService;
use Illuminate\Http\JsonResponse;

/**
 * Handles bulk import of Twitch user stats from external sources
 */
class TwitchStatsController extends Controller
{
    /**
     * Create a new controller instance
     */
    public function __construct(
        private TwitchStatsService $statsService
    ) {}

    /**
     * Bulk import/update Twitch user stats from globals.db
     */
    public function bulkImport(BulkImportStatsRequest $request): JsonResponse
    {
        try {
            $result = $this->statsService->importStats($request->validated()['stats']);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to import Twitch stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
