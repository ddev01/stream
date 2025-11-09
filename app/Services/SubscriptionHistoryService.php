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
            // Step 1: Get all unique user IDs from raw data and normalize them
            // We normalize first, then query database with ONLY normalized IDs
            $userIds = [];
            foreach ($subscriptionHistory as $sub) {
                if (isset($sub['userId'])) {
                    $normalized = $this->normalizeUserId($sub['userId']);
                    if ($normalized) {
                        $userIds[] = $normalized;
                    }
                }
                if (isset($sub['gifterUserId'])) {
                    $normalized = $this->normalizeUserId($sub['gifterUserId']);
                    if ($normalized) {
                        $userIds[] = $normalized;
                    }
                }
            }
            $userIds = array_unique($userIds);
            $userIds = array_values($userIds);

            // Step 2: Verify all user_ids exist in twitch_users (single query)
            if (empty($userIds)) {
                return [
                    'status' => 'success',
                    'message' => 'No user IDs found in subscription history',
                    'imported' => 0,
                    'updated' => 0,
                    'total' => count($subscriptionHistory),
                    'valid' => 0,
                ];
            }

            // Query database with normalized string IDs
            $existingUsers = TwitchUser::whereIn('twitch_id', $userIds)
                ->pluck('twitch_id')
                ->toArray();

            // Add dummy user "0" for missing users
            $existingUsers[] = '0';
            $existingUsers = array_unique($existingUsers);
            $existingUsers = array_values($existingUsers);

            // Step 3: Extract and prepare subscription history data (with user verification)
            $subscriptionData = $this->prepareSubscriptionData($subscriptionHistory, $existingUsers);

            if (empty($subscriptionData)) {
                DB::rollBack();

                return [
                    'status' => 'warning',
                    'message' => 'No valid subscription history to import - all records were filtered out',
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

            return [
                'status' => ($imported > 0 || $updated > 0) ? 'success' : 'warning',
                'message' => ($imported > 0 || $updated > 0)
                    ? 'Subscription history imported successfully'
                    : 'Subscription history import completed but no records were imported or updated',
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
                continue;
            }

            // Extract user_id and gifter_user_id (normalize for comparison)
            $userId = isset($sub['userId']) ? $this->normalizeUserId($sub['userId']) : null;

            // Use dummy user "0" if userId is missing or null
            if (! $userId || $userId === '') {
                $userId = '0';
            }

            // Use dummy user "0" if user doesn't exist
            if (! in_array($userId, $existingUsers, true)) {
                $userId = '0';
            }

            $gifterUserId = isset($sub['gifterUserId']) ? $this->normalizeUserId($sub['gifterUserId']) : null;

            // Use dummy user "0" if gifterUserId is missing or null
            if (! $gifterUserId || $gifterUserId === '') {
                $gifterUserId = '0';
            }

            // Use dummy user "0" if gifter doesn't exist
            if (! in_array($gifterUserId, $existingUsers, true)) {
                $gifterUserId = '0';
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

    /**
     * Normalize user ID to a plain string for consistent comparison
     * Converts any type (int, string, JSON-encoded) to a plain string
     */
    private function normalizeUserId(mixed $userId): ?string
    {
        if ($userId === null) {
            return null;
        }

        // Convert to string
        $normalized = is_string($userId) ? $userId : (string) $userId;
        $normalized = trim($normalized);

        if ($normalized === '') {
            return null;
        }

        // Keep decoding JSON until we can't decode anymore
        $maxIterations = 10;
        $iterations = 0;

        while ($iterations < $maxIterations) {
            // Try to JSON decode
            $decoded = json_decode($normalized, true);

            // If decode was successful and returned something different, use it
            if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
                $decodedString = is_string($decoded) ? $decoded : (string) $decoded;
                $decodedString = trim($decodedString);

                // If decoded value is the same as the original (after trimming), we're done (prevents infinite loop)
                if ($decodedString === $normalized) {
                    break;
                }

                $normalized = $decodedString;
                $iterations++;

                continue;
            }

            // If we can't decode anymore, we're done
            break;
        }

        return $normalized === '' ? null : $normalized;
    }
}
