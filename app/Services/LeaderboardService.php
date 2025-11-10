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
        return TwitchUserStat::with('twitchUser')
            ->forStat($statName)
            ->orderedByValue('desc')
            ->limit($limit)
            ->get();
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

        $position = TwitchUserStat::forStat($statName)
            ->where('value', '>', $userStat->value)
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
        return SubscriptionHistory::query()
            ->selectRaw('gifter_user_id, COUNT(*) as gift_count')
            ->whereNotNull('gifter_user_id')
            ->groupBy('gifter_user_id')
            ->orderByDesc('gift_count')
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

        // Count how many users have given more subscriptions
        $position = SubscriptionHistory::query()
            ->selectRaw('gifter_user_id, COUNT(*) as gift_count')
            ->whereNotNull('gifter_user_id')
            ->groupBy('gifter_user_id')
            ->havingRaw('COUNT(*) > ?', [$giftCount])
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
        return RaidHistory::query()
            ->selectRaw('user_id, COUNT(*) as raid_count')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('raid_count')
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
    }

    /**
     * Get top raiders by total viewers raided
     */
    public function getTopRaiderViews(int $limit = 5): Collection
    {
        return RaidHistory::query()
            ->selectRaw('user_id, SUM(viewers) as total_viewers')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('total_viewers')
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

        // Count how many users have raided more times
        $position = RaidHistory::query()
            ->selectRaw('user_id, COUNT(*) as raid_count')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > ?', [$raidCount])
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

        // Count how many users have raided more total viewers
        $position = RaidHistory::query()
            ->selectRaw('user_id, SUM(viewers) as total_viewers')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('SUM(viewers) > ?', [$totalViewers])
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
