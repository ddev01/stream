<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BulkImportRaidHistoryRequest;
use App\Jobs\ImportRaidHistory;
use Illuminate\Http\JsonResponse;

/**
 * Handles bulk import of raid history from external sources
 */
class RaidHistoryController extends Controller
{
    /**
     * Bulk import/update raid history (queued for async processing)
     */
    public function bulkImport(BulkImportRaidHistoryRequest $request): JsonResponse
    {
        $raidHistory = $request->validated()['raid_history'];

        // Dispatch job to queue for async processing
        ImportRaidHistory::dispatch($raidHistory);

        // Return immediate response (202 Accepted)
        return response()->json([
            'status' => 'queued',
            'message' => 'Raid history import queued for processing',
            'records' => count($raidHistory),
        ], 202);
    }
}
