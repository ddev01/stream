<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BulkImportSubscriptionHistoryRequest;
use App\Jobs\ImportSubscriptionHistory;
use Illuminate\Http\JsonResponse;

/**
 * Handles bulk import of subscription history from external sources
 */
class SubscriptionHistoryController extends Controller
{
    /**
     * Bulk import/update subscription history (queued for async processing)
     */
    public function bulkImport(BulkImportSubscriptionHistoryRequest $request): JsonResponse
    {
        $subscriptionHistory = $request->validated()['subscription_history'];

        // Dispatch job to queue for async processing
        ImportSubscriptionHistory::dispatch($subscriptionHistory);

        // Return immediate response (202 Accepted)
        return response()->json([
            'status' => 'queued',
            'message' => 'Subscription history import queued for processing',
            'records' => count($subscriptionHistory),
        ], 202);
    }
}
