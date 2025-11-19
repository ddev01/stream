<?php

namespace App\Livewire\Concerns;

trait FormatsUserLinks
{
    /**
     * Format user link view data for a TwitchUserStat row
     */
    protected function formatUserLink($row): array
    {
        $twitchUser = $row->getRelation('twitchUser') ?? $row->twitchUser ?? null;

        if (! $twitchUser) {
            return [
                'displayName' => 'Unknown',
                'hasUser' => false,
                'twitchId' => null,
            ];
        }

        return [
            'displayName' => $twitchUser->display_name ?? 'Unknown',
            'hasUser' => $twitchUser->relationLoaded('user') ? $twitchUser->user !== null : ($twitchUser->user_id !== null),
            'twitchId' => $twitchUser->twitch_id ?? null,
        ];
    }
}
