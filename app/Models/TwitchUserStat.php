<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents an arbitrary stat for a Twitch user
 */
class TwitchUserStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'twitch_user_id',
        'name',
        'value',
        'last_write',
    ];

    /**
     * Get the attributes that should be cast
     */
    protected function casts(): array
    {
        return [
            'last_write' => 'datetime',
        ];
    }

    /**
     * Get the Twitch user that owns this stat
     */
    public function twitchUser(): BelongsTo
    {
        return $this->belongsTo(TwitchUser::class);
    }
}
