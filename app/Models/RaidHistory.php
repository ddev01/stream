<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Raid history from Twitch
 */
class RaidHistory extends Model
{
    protected $table = 'raid_history';

    protected $fillable = [
        'oid',
        'user_id',
        'viewers',
        'timestamp',
    ];

    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
            'viewers' => 'integer',
        ];
    }

    /**
     * Get the user who initiated the raid
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(TwitchUser::class, 'user_id', 'twitch_id');
    }
}
