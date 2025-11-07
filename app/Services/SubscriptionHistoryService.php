<?php

namespace App\Services;

use App\Models\SubscriptionHistory;
use App\Models\TwitchUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling subscription history import and management
 */
class SubscriptionHistoryService
{
    /**
     * Import subscription history in bulk with optimized batch operations
     */
    public function importSubscriptionHistory(array $subscriptionHistory): array
    {
        $imported = 0;
        $updated = 0;

        if (empty($subscriptionHistory)) {
            return [
                'status' => 'success',
                'message' => 'No subscription history to import',
                'imported' => 0,
                'updated' => 0,
                'total' => 0,
            ];
        }

        DB::beginTransaction();

        try {
            // Step 1: Get all unique user IDs from raw data (before processing)
            $userIds = [];
            foreach ($subscriptionHistory as $sub) {
                if (isset($sub['userId'])) {
                    $userIds[] = $sub['userId'];
                }
                if (isset($sub['gifterUserId'])) {
                    $userIds[] = $sub['gifterUserId'];
                }
            }
            $userIds = array_unique($userIds);

            // Step 2: Verify all user_ids exist in twitch_users (single query)
            $existingUsers = TwitchUser::whereIn('twitch_id', $userIds)
                ->pluck('twitch_id')
                ->toArray();
            $missingUsers = array_diff($userIds, $existingUsers);

            if (! empty($missingUsers)) {
                Log::warning('Subscription history import: Some users do not exist', [
                    'missing_users' => $missingUsers,
                    'total_missing' => count($missingUsers),
                ]);
            }

            // Step 3: Extract and prepare subscription history data (with user verification)
            $subscriptionData = $this->prepareSubscriptionData($subscriptionHistory, $existingUsers);

            if (empty($subscriptionData)) {
                DB::rollBack();

                return [
                    'status' => 'success',
                    'message' => 'No valid subscription history to import',
                    'imported' => 0,
                    'updated' => 0,
                    'total' => count($subscriptionHistory),
                    'valid' => 0,
                ];
            }

            // Step 4: Track existing subscription history for counting
            $oids = array_column($subscriptionData, 'oid');
            $existingSubscriptionHistory = SubscriptionHistory::whereIn('oid', $oids)
                ->pluck('oid')
                ->toArray();

            // Step 5: Batch upsert subscription history (chunked for performance)
            if (! empty($subscriptionData)) {
                // Chunk large datasets to optimize performance and memory usage
                foreach (array_chunk($subscriptionData, 250) as $chunk) {
                    DB::table('subscription_history')->upsert(
                        $chunk,
                        ['oid'], // Unique key
                        ['user_id', 'gifter_user_id', 'subscribed_at', 'updated_at'] // Fields to update
                    );
                }
            }

            // Step 6: Calculate imported vs updated counts
            foreach ($subscriptionData as $sub) {
                if (in_array($sub['oid'], $existingSubscriptionHistory)) {
                    $updated++;
                } else {
                    $imported++;
                }
            }

            DB::commit();

            Log::info('Subscription history import completed', [
                'imported' => $imported,
                'updated' => $updated,
                'total' => count($subscriptionHistory),
                'valid' => count($subscriptionData),
            ]);

            return [
                'status' => 'success',
                'message' => 'Subscription history imported successfully',
                'imported' => $imported,
                'updated' => $updated,
                'total' => count($subscriptionHistory),
                'valid' => count($subscriptionData),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Subscription history bulk import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Prepare subscription history data from raw API payload
     * Filters out records with invalid or missing users during preparation
     */
    private function prepareSubscriptionData(array $subscriptionHistory, array $existingUsers): array
    {
        $subscriptionData = [];

        foreach ($subscriptionHistory as $sub) {
            // Extract oid from _id.$oid
            $oid = $sub['_id']['$oid'] ?? null;
            if (! $oid) {
                continue;
            }

            // Extract subscribed_at from subscribedAt.$date
            $subscribedAt = $sub['subscribedAt']['$date'] ?? null;
            if (! $subscribedAt) {
                continue;
            }

            // Parse the ISO 8601 date string
            try {
                $subscribedAtDate = Carbon::parse($subscribedAt);
            } catch (\Exception $e) {
                Log::warning('Invalid date format in subscription', [
                    'oid' => $oid,
                    'subscribedAt' => $subscribedAt,
                ]);

                continue;
            }

            // Extract user_id and gifter_user_id
            $userId = $sub['userId'] ?? null;
            if (! $userId) {
                continue;
            }

            // Verify user exists before processing
            if (! in_array($userId, $existingUsers)) {
                continue;
            }

            $gifterUserId = $sub['gifterUserId'] ?? null;
            if (! $gifterUserId) {
                continue;
            }

            // Verify gifter exists
            if (! in_array($gifterUserId, $existingUsers)) {
                continue;
            }

            $subscriptionData[] = [
                'oid' => $oid,
                'user_id' => $userId,
                'gifter_user_id' => $gifterUserId,
                'subscribed_at' => $subscribedAtDate->format('Y-m-d H:i:s'),
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ];
        }

        return $subscriptionData;
    }
}
