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
                'userId' => null,
            ];
        }

        return [
            'displayName' => $twitchUser->display_name ?? 'Unknown',
            'hasUser' => $twitchUser->relationLoaded('user') ? $twitchUser->user !== null : ($twitchUser->user_id !== null),
            'userId' => $twitchUser->user?->id ?? ($twitchUser->user_id ?? null),
        ];
    }
}
