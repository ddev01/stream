<?php

namespace App\Services;

use App\Models\RaidHistory;
use App\Models\SubscriptionHistory;
use App\Models\TwitchUserStat;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Service for handling leaderboard queries and user stat retrieval
 */
class LeaderboardService
{
    /**
     * Get top stats for a specific stat name
     */
    public function getTopStats(string $statName, int $limit = 5): Collection
    {
        $topStats = TwitchUserStat::with('twitchUser')
            ->forStat($statName)
            ->orderedByValue('desc')
            ->orderBy('id', 'asc') // Tie-breaker: lower ID appears first
            ->limit($limit)
            ->get();

        // Calculate sequential position for each stat (no ties - each gets unique position)
        // Use value DESC, then id ASC as tie-breaker for consistent ordering
        return $topStats->map(function ($stat) use ($statName) {
            // Count users with higher value OR same value but lower ID (appears first in sort)
            $position = TwitchUserStat::forStat($statName)
                ->where(function ($query) use ($stat) {
                    $query->whereRaw('CAST(value AS INTEGER) > ?', [$stat->value])
                        ->orWhere(function ($q) use ($stat) {
                            $q->whereRaw('CAST(value AS INTEGER) = ?', [$stat->value])
                                ->where('id', '<', $stat->id);
                        });
                })
                ->count() + 1;

            // Add position as an attribute
            $stat->position = $position;

            return $stat;
        });
    }

    /**
     * Get user's stat and position for a specific stat name
     *
     * @return array{stat: TwitchUserStat|null, position: int|null}
     */
    public function getUserStatAndPosition(User $user, string $statName): array
    {
        $twitchUser = $user->twitchUser;

        if (! $twitchUser) {
            return ['stat' => null, 'position' => null];
        }

        $userStat = TwitchUserStat::where('twitch_user_id', $twitchUser->id)
            ->forStat($statName)
            ->first();

        if (! $userStat) {
            return ['stat' => null, 'position' => null];
        }

        // Calculate sequential position (no ties - each gets unique position)
        // Use value DESC, then id ASC as tie-breaker for consistent ordering
        // Count users with higher value OR same value but lower ID (appears first in sort)
        $position = TwitchUserStat::forStat($statName)
            ->where(function ($query) use ($userStat) {
                $query->whereRaw('CAST(value AS INTEGER) > ?', [$userStat->value])
                    ->orWhere(function ($q) use ($userStat) {
                        $q->whereRaw('CAST(value AS INTEGER) = ?', [$userStat->value])
                            ->where('id', '<', $userStat->id);
                    });
            })
            ->count() + 1;

        return [
            'stat' => $userStat,
            'position' => $position,
        ];
    }

    /**
     * Get top gifters (users who have given the most subscriptions)
     */
    public function getTopGifters(int $limit = 5): Collection
    {
        $topGifters = SubscriptionHistory::query()
            ->selectRaw('gifter_user_id, COUNT(*) as gift_count')
            ->whereNotNull('gifter_user_id')
            ->groupBy('gifter_user_id')
            ->orderByDesc('gift_count')
            ->orderBy('gifter_user_id', 'asc') // Tie-breaker: lower ID appears first
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $twitchUser = \App\Models\TwitchUser::where('twitch_id', $item->gifter_user_id)->first();

                // Create a simple object that mimics TwitchUserStat structure for the card component
                return (object) [
                    'gift_count' => $item->gift_count,
                    'twitchUser' => $twitchUser,
                    'twitch_user_id' => $item->gifter_user_id,
                    'value' => $item->gift_count, // For compatibility with leaderboard card
                ];
            })
            ->filter(fn ($item) => $item->twitchUser !== null); // Filter out users that don't exist

        // Calculate sequential position for each gifter (no ties - each gets unique position)
        // Use gift_count DESC, then gifter_user_id ASC as tie-breaker
        return $topGifters->map(function ($item) {
            $position = SubscriptionHistory::query()
                ->selectRaw('gifter_user_id, COUNT(*) as gift_count')
                ->whereNotNull('gifter_user_id')
                ->groupBy('gifter_user_id')
                ->havingRaw('COUNT(*) > ? OR (COUNT(*) = ? AND gifter_user_id < ?)', [
                    $item->gift_count,
                    $item->gift_count,
                    $item->twitch_user_id, // Use twitch_user_id which is the same as gifter_user_id
                ])
                ->count() + 1;

            $item->position = $position;

            return $item;
        });
    }

    /**
     * Get user's gifted subscription count and position
     *
     * @return array{stat: object|null, position: int|null}
     */
    public function getUserGifterStatAndPosition(User $user): array
    {
        $twitchUser = $user->twitchUser;

        if (! $twitchUser) {
            return ['stat' => null, 'position' => null];
        }

        $giftCount = SubscriptionHistory::where('gifter_user_id', $twitchUser->twitch_id)
            ->count();

        if ($giftCount === 0) {
            return ['stat' => null, 'position' => null];
        }

        // Calculate sequential position (no ties - each gets unique position)
        // Use gift_count DESC, then gifter_user_id ASC as tie-breaker
        $position = SubscriptionHistory::query()
            ->selectRaw('gifter_user_id, COUNT(*) as gift_count')
            ->whereNotNull('gifter_user_id')
            ->groupBy('gifter_user_id')
            ->havingRaw('COUNT(*) > ? OR (COUNT(*) = ? AND gifter_user_id < ?)', [
                $giftCount,
                $giftCount,
                $twitchUser->twitch_id,
            ])
            ->count() + 1;

        $stat = (object) [
            'gift_count' => $giftCount,
            'value' => $giftCount, // For compatibility with leaderboard card
        ];

        return [
            'stat' => $stat,
            'position' => $position,
        ];
    }

    /**
     * Get top raiders (users who have raided the most times)
     */
    public function getTopRaiders(int $limit = 5): Collection
    {
        $topRaiders = RaidHistory::query()
            ->selectRaw('user_id, COUNT(*) as raid_count')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('raid_count')
            ->orderBy('user_id', 'asc') // Tie-breaker: lower ID appears first
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $twitchUser = \App\Models\TwitchUser::where('twitch_id', $item->user_id)->first();

                // Create a simple object that mimics TwitchUserStat structure for the card component
                return (object) [
                    'raid_count' => $item->raid_count,
                    'twitchUser' => $twitchUser,
                    'twitch_user_id' => $item->user_id,
                    'value' => $item->raid_count, // For compatibility with leaderboard card
                ];
            })
            ->filter(fn ($item) => $item->twitchUser !== null); // Filter out users that don't exist

        // Calculate sequential position for each raider (no ties - each gets unique position)
        // Use raid_count DESC, then user_id ASC as tie-breaker
        return $topRaiders->map(function ($item) {
            $position = RaidHistory::query()
                ->selectRaw('user_id, COUNT(*) as raid_count')
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > ? OR (COUNT(*) = ? AND user_id < ?)', [
                    $item->raid_count,
                    $item->raid_count,
                    $item->twitch_user_id, // Use twitch_user_id which is the same as user_id
                ])
                ->count() + 1;

            $item->position = $position;

            return $item;
        });
    }

    /**
     * Get top raiders by total viewers raided
     */
    public function getTopRaiderViews(int $limit = 5): Collection
    {
        $topRaiderViews = RaidHistory::query()
            ->selectRaw('user_id, SUM(viewers) as total_viewers')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('total_viewers')
            ->orderBy('user_id', 'asc') // Tie-breaker: lower ID appears first
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $twitchUser = \App\Models\TwitchUser::where('twitch_id', $item->user_id)->first();

                // Create a simple object that mimics TwitchUserStat structure for the card component
                return (object) [
                    'total_viewers' => $item->total_viewers,
                    'twitchUser' => $twitchUser,
                    'twitch_user_id' => $item->user_id,
                    'value' => $item->total_viewers, // For compatibility with leaderboard card
                ];
            })
            ->filter(fn ($item) => $item->twitchUser !== null); // Filter out users that don't exist

        // Calculate sequential position for each raider views (no ties - each gets unique position)
        // Use total_viewers DESC, then user_id ASC as tie-breaker
        return $topRaiderViews->map(function ($item) {
            $position = RaidHistory::query()
                ->selectRaw('user_id, SUM(viewers) as total_viewers')
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->havingRaw('SUM(viewers) > ? OR (SUM(viewers) = ? AND user_id < ?)', [
                    $item->total_viewers,
                    $item->total_viewers,
                    $item->twitch_user_id, // Use twitch_user_id which is the same as user_id
                ])
                ->count() + 1;

            $item->position = $position;

            return $item;
        });
    }

    /**
     * Get recent raid history (last N raids)
     */
    public function getRecentRaidHistory(int $limit = 5): Collection
    {
        return RaidHistory::with('user')
            ->orderByDesc('timestamp')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user's raid count and position
     *
     * @return array{stat: object|null, position: int|null}
     */
    public function getUserRaiderStatAndPosition(User $user): array
    {
        $twitchUser = $user->twitchUser;

        if (! $twitchUser) {
            return ['stat' => null, 'position' => null];
        }

        $raidCount = RaidHistory::where('user_id', $twitchUser->twitch_id)
            ->count();

        if ($raidCount === 0) {
            return ['stat' => null, 'position' => null];
        }

        // Calculate sequential position (no ties - each gets unique position)
        // Use raid_count DESC, then user_id ASC as tie-breaker
        $position = RaidHistory::query()
            ->selectRaw('user_id, COUNT(*) as raid_count')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > ? OR (COUNT(*) = ? AND user_id < ?)', [
                $raidCount,
                $raidCount,
                $twitchUser->twitch_id,
            ])
            ->count() + 1;

        $stat = (object) [
            'raid_count' => $raidCount,
            'value' => $raidCount, // For compatibility with leaderboard card
        ];

        return [
            'stat' => $stat,
            'position' => $position,
        ];
    }

    /**
     * Get user's total viewers raided and position
     *
     * @return array{stat: object|null, position: int|null}
     */
    public function getUserRaiderViewsStatAndPosition(User $user): array
    {
        $twitchUser = $user->twitchUser;

        if (! $twitchUser) {
            return ['stat' => null, 'position' => null];
        }

        $totalViewers = RaidHistory::where('user_id', $twitchUser->twitch_id)
            ->sum('viewers');

        if ($totalViewers === 0) {
            return ['stat' => null, 'position' => null];
        }

        // Calculate sequential position (no ties - each gets unique position)
        // Use total_viewers DESC, then user_id ASC as tie-breaker
        $position = RaidHistory::query()
            ->selectRaw('user_id, SUM(viewers) as total_viewers')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('SUM(viewers) > ? OR (SUM(viewers) = ? AND user_id < ?)', [
                $totalViewers,
                $totalViewers,
                $twitchUser->twitch_id,
            ])
            ->count() + 1;

        $stat = (object) [
            'total_viewers' => $totalViewers,
            'value' => $totalViewers, // For compatibility with leaderboard card
        ];

        return [
            'stat' => $stat,
            'position' => $position,
        ];
    }
}
