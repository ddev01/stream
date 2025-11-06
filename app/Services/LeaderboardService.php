<?php

namespace App\Services;

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
}
