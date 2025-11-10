<?php

namespace App\Services;

use App\Models\RaidHistory;
use App\Models\TwitchUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling raid history import and management
 */
class RaidHistoryService
{
    /**
     * Import raid history in bulk with optimized batch operations
     */
    public function importRaidHistory(array $raidHistory): array
    {
        $imported = 0;
        $updated = 0;

        if (empty($raidHistory)) {
            return [
                'status' => 'success',
                'message' => 'No raid history to import',
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
            foreach ($raidHistory as $raid) {
                if (isset($raid['userId'])) {
                    $normalized = $this->normalizeUserId($raid['userId']);
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
                    'message' => 'No user IDs found in raid history',
                    'imported' => 0,
                    'updated' => 0,
                    'total' => count($raidHistory),
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

            // Step 3: Extract and prepare raid history data (with user verification)
            $raidData = $this->prepareRaidData($raidHistory, $existingUsers);

            if (empty($raidData)) {
                DB::rollBack();

                return [
                    'status' => 'warning',
                    'message' => 'No valid raid history to import - all records were filtered out',
                    'imported' => 0,
                    'updated' => 0,
                    'total' => count($raidHistory),
                    'valid' => 0,
                ];
            }

            // Step 4: Track existing raid history for counting
            $oids = array_column($raidData, 'oid');
            $existingRaidHistory = RaidHistory::whereIn('oid', $oids)
                ->pluck('oid')
                ->toArray();

            // Step 5: Batch upsert raid history (chunked for performance)
            if (! empty($raidData)) {
                // Chunk large datasets to optimize performance and memory usage
                foreach (array_chunk($raidData, 250) as $chunk) {
                    DB::table('raid_history')->upsert(
                        $chunk,
                        ['oid'], // Unique key
                        ['user_id', 'viewers', 'timestamp', 'updated_at'] // Fields to update
                    );
                }
            }

            // Step 6: Calculate imported vs updated counts
            foreach ($raidData as $raid) {
                if (in_array($raid['oid'], $existingRaidHistory)) {
                    $updated++;
                } else {
                    $imported++;
                }
            }

            DB::commit();

            return [
                'status' => ($imported > 0 || $updated > 0) ? 'success' : 'warning',
                'message' => ($imported > 0 || $updated > 0)
                    ? 'Raid history imported successfully'
                    : 'Raid history import completed but no records were imported or updated',
                'imported' => $imported,
                'updated' => $updated,
                'total' => count($raidHistory),
                'valid' => count($raidData),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Raid history bulk import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Prepare raid history data from raw API payload
     */
    private function prepareRaidData(array $raidHistory, array $existingUsers): array
    {
        $raidData = [];

        foreach ($raidHistory as $raid) {
            // Extract oid from _id.$oid
            $oid = $raid['_id']['$oid'] ?? null;
            if (! $oid) {
                continue;
            }

            // Extract timestamp from timestamp.$date
            $timestamp = $raid['timestamp']['$date'] ?? null;
            if (! $timestamp) {
                continue;
            }

            // Parse the ISO 8601 date string
            try {
                $timestampDate = Carbon::parse($timestamp);
            } catch (\Exception $e) {
                continue;
            }

            // Extract user_id (normalize for comparison)
            $userId = isset($raid['userId']) ? $this->normalizeUserId($raid['userId']) : null;

            // Use dummy user "0" if userId is missing or null
            if (! $userId || $userId === '') {
                $userId = '0';
            }

            // Use dummy user "0" if user doesn't exist
            if (! in_array($userId, $existingUsers, true)) {
                $userId = '0';
            }

            // Extract viewers (default to 0 if missing)
            $viewers = isset($raid['viewers']) ? (int) $raid['viewers'] : 0;

            $raidData[] = [
                'oid' => $oid,
                'user_id' => $userId,
                'viewers' => $viewers,
                'timestamp' => $timestampDate->format('Y-m-d H:i:s'),
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ];
        }

        return $raidData;
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
