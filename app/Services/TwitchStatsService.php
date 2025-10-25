<?php

namespace App\Services;

use App\Models\TwitchUser;
use App\Models\TwitchUserStat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling Twitch user stats import and management
 */
class TwitchStatsService
{
    /**
     * Import stats in bulk with optimized batch operations
     */
    public function importStats(array $stats): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $imported = 0;
        $updated = 0;

        if (empty($stats)) {
            return [
                'status' => 'success',
                'message' => 'No stats to import',
                'imported' => 0,
                'updated' => 0,
                'total' => 0,
            ];
        }

        DB::beginTransaction();

        try {
            $dbStartTime = microtime(true);

            // Step 1: Prepare unique Twitch users data
            $uniqueUsers = $this->prepareUniqueUsers($stats);

            // Step 2: Batch upsert Twitch users (chunked for performance)
            $userUpsertStart = microtime(true);
            if (! empty($uniqueUsers)) {
                // Chunk large datasets to optimize performance
                foreach (array_chunk($uniqueUsers, 250) as $chunk) {
                    DB::table('twitch_users')->upsert(
                        $chunk,
                        ['twitch_id'],
                        ['display_name', 'updated_at']
                    );
                }
            }
            $userUpsertDuration = (microtime(true) - $userUpsertStart) * 1000;

            // Step 3: Get all twitch_user_id mappings for stats
            $mappingStart = microtime(true);
            $twitchIds = array_column($uniqueUsers, 'twitch_id');
            $twitchUserMap = TwitchUser::whereIn('twitch_id', $twitchIds)
                ->pluck('id', 'twitch_id')
                ->toArray();
            $mappingDuration = (microtime(true) - $mappingStart) * 1000;

            // Step 4: Prepare stats data with twitch_user_id
            $prepareStart = microtime(true);
            $statsData = $this->prepareStatsData($stats, $twitchUserMap);
            $prepareDuration = (microtime(true) - $prepareStart) * 1000;

            // Step 5: Track existing stats for counting
            $existingStart = microtime(true);
            $existingStats = $this->getExistingStats($statsData);
            $existingDuration = (microtime(true) - $existingStart) * 1000;

            // Step 6: Batch upsert stats (chunked for performance)
            $statsUpsertStart = microtime(true);
            if (! empty($statsData)) {
                // Chunk large datasets to optimize performance and memory usage
                foreach (array_chunk($statsData, 500) as $chunk) {
                    DB::table('twitch_user_stats')->upsert(
                        $chunk,
                        ['twitch_user_id', 'name'],
                        ['value', 'last_write', 'updated_at']
                    );
                }
            }
            $statsUpsertDuration = (microtime(true) - $statsUpsertStart) * 1000;

            $dbDuration = (microtime(true) - $dbStartTime) * 1000;

            // Step 7: Calculate imported vs updated counts
            foreach ($statsData as $stat) {
                $key = $stat['twitch_user_id'].'_'.$stat['name'];
                if (isset($existingStats[$key])) {
                    $updated++;
                } else {
                    $imported++;
                }
            }

            DB::commit();

            $totalDuration = (microtime(true) - $startTime) * 1000;
            $peakMemory = memory_get_peak_usage(true);
            $memoryUsed = $peakMemory - $startMemory;

            // Detailed performance logging for benchmarking
            Log::info('BENCHMARK: Twitch stats import', [
                'timing' => [
                    'total_ms' => round($totalDuration, 2),
                    'database_total_ms' => round($dbDuration, 2),
                    'user_upsert_ms' => round($userUpsertDuration, 2),
                    'user_mapping_ms' => round($mappingDuration, 2),
                    'stats_prepare_ms' => round($prepareDuration, 2),
                    'existing_check_ms' => round($existingDuration, 2),
                    'stats_upsert_ms' => round($statsUpsertDuration, 2),
                ],
                'records' => [
                    'total_received' => count($stats),
                    'unique_users' => count($uniqueUsers),
                    'stats_processed' => count($statsData),
                    'imported' => $imported,
                    'updated' => $updated,
                ],
                'memory' => [
                    'peak_mb' => round($peakMemory / 1024 / 1024, 2),
                    'used_mb' => round($memoryUsed / 1024 / 1024, 2),
                ],
            ]);

            return [
                'status' => 'success',
                'message' => 'Twitch stats imported successfully',
                'imported' => $imported,
                'updated' => $updated,
                'total' => count($stats),
                'duration_ms' => round($totalDuration, 2),
                'memory_mb' => round($memoryUsed / 1024 / 1024, 2),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Twitch stats bulk import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Prepare unique Twitch users from stats data
     */
    private function prepareUniqueUsers(array $stats): array
    {
        $users = [];
        $seen = [];

        foreach ($stats as $statData) {
            $twitchId = $statData['userId'];

            if (! isset($seen[$twitchId])) {
                $users[] = [
                    'twitch_id' => $twitchId,
                    'display_name' => $statData['userName'] ?? null,
                    'updated_at' => now(),
                ];
                $seen[$twitchId] = true;
            }
        }

        return $users;
    }

    /**
     * Prepare stats data with twitch_user_id mappings
     */
    private function prepareStatsData(array $stats, array $twitchUserMap): array
    {
        $statsData = [];

        foreach ($stats as $statData) {
            $twitchUserId = $twitchUserMap[$statData['userId']] ?? null;

            if (! $twitchUserId) {
                continue;
            }

            $value = $statData['value'];
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }

            $statsData[] = [
                'twitch_user_id' => $twitchUserId,
                'name' => $statData['name'],
                'value' => $value,
                'last_write' => $statData['lastWrite'] ?? now(),
                'updated_at' => now(),
            ];
        }

        return $statsData;
    }

    /**
     * Get existing stats for counting purposes (optimized)
     */
    private function getExistingStats(array $statsData): array
    {
        if (empty($statsData)) {
            return [];
        }

        // Build array of composite keys for efficient lookup
        $keys = array_map(
            fn ($stat) => $stat['twitch_user_id'].'|'.$stat['name'],
            $statsData
        );

        // Single efficient query using CONCAT and whereIn
        $existing = TwitchUserStat::select('twitch_user_id', 'name')
            ->whereIn(
                DB::raw("CONCAT(twitch_user_id, '|', name)"),
                $keys
            )
            ->get();

        // Build lookup map
        $map = [];
        foreach ($existing as $stat) {
            $key = $stat->twitch_user_id.'_'.$stat->name;
            $map[$key] = true;
        }

        return $map;
    }
}
