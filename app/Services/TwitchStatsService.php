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
            // Step 1: Prepare unique Twitch users data
            $uniqueUsers = $this->prepareUniqueUsers($stats);

            // Step 2: Batch upsert Twitch users
            if (! empty($uniqueUsers)) {
                DB::table('twitch_users')->upsert(
                    $uniqueUsers,
                    ['twitch_id'],
                    ['display_name', 'updated_at']
                );
            }

            // Step 3: Get all twitch_user_id mappings for stats
            $twitchIds = array_column($uniqueUsers, 'twitch_id');
            $twitchUserMap = TwitchUser::whereIn('twitch_id', $twitchIds)
                ->pluck('id', 'twitch_id')
                ->toArray();

            // Step 4: Prepare stats data with twitch_user_id
            $statsData = $this->prepareStatsData($stats, $twitchUserMap);

            // Step 5: Track existing stats for counting
            $existingStats = $this->getExistingStats($statsData);

            // Step 6: Batch upsert stats
            if (! empty($statsData)) {
                DB::table('twitch_user_stats')->upsert(
                    $statsData,
                    ['twitch_user_id', 'name'],
                    ['value', 'last_write', 'updated_at']
                );
            }

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

            Log::info('Twitch stats bulk import completed', [
                'imported' => $imported,
                'updated' => $updated,
                'total' => count($stats),
            ]);

            return [
                'status' => 'success',
                'message' => 'Twitch stats imported successfully',
                'imported' => $imported,
                'updated' => $updated,
                'total' => count($stats),
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
     * Get existing stats for counting purposes
     */
    private function getExistingStats(array $statsData): array
    {
        $conditions = [];
        foreach ($statsData as $stat) {
            $conditions[] = [
                'twitch_user_id' => $stat['twitch_user_id'],
                'name' => $stat['name'],
            ];
        }

        if (empty($conditions)) {
            return [];
        }

        $existing = TwitchUserStat::where(function ($query) use ($conditions) {
            foreach ($conditions as $condition) {
                $query->orWhere(function ($q) use ($condition) {
                    $q->where('twitch_user_id', $condition['twitch_user_id'])
                        ->where('name', $condition['name']);
                });
            }
        })->get();

        $map = [];
        foreach ($existing as $stat) {
            $key = $stat->twitch_user_id.'_'.$stat->name;
            $map[$key] = true;
        }

        return $map;
    }
}
