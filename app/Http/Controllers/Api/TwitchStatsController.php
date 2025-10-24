<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TwitchUser;
use App\Models\TwitchUserStat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Handles bulk import of Twitch user stats from external sources
 */
class TwitchStatsController extends Controller
{
    /**
     * Bulk import/update Twitch user stats from globals.db
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'stats' => 'present|array|min:0',
            'stats.*.userId' => 'required|string',
            'stats.*.userName' => 'nullable|string',
            'stats.*.platform' => 'nullable|string',
            'stats.*.name' => 'required|string',
            'stats.*.value' => 'required',
            'stats.*.lastWrite' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $stats = $request->input('stats');
        $imported = 0;
        $updated = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($stats as $statData) {
                // Find or create the Twitch user, and update username if provided
                $twitchUser = TwitchUser::updateOrCreate(
                    ['twitch_id' => $statData['userId']],
                    [
                        'display_name' => $statData['userName'] ?? null,
                    ]
                );

                // Upsert the stat
                $stat = TwitchUserStat::updateOrCreate(
                    [
                        'twitch_user_id' => $twitchUser->id,
                        'name' => $statData['name'],
                    ],
                    [
                        'value' => is_array($statData['value']) || is_object($statData['value'])
                            ? json_encode($statData['value'])
                            : $statData['value'],
                        'last_write' => $statData['lastWrite'] ?? now(),
                    ]
                );

                if ($stat->wasRecentlyCreated) {
                    $imported++;
                } else {
                    $updated++;
                }
            }

            DB::commit();

            Log::info('Twitch stats bulk import completed', [
                'imported' => $imported,
                'updated' => $updated,
                'total' => count($stats),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Twitch stats imported successfully',
                'imported' => $imported,
                'updated' => $updated,
                'total' => count($stats),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Twitch stats bulk import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to import Twitch stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
