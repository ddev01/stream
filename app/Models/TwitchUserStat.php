<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
            'value' => 'integer',
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

    /**
     * Scope a query to filter by stat name
     */
    public function scopeForStat(Builder $query, string $statName): Builder
    {
        return $query->where('name', $statName);
    }

    /**
     * Scope a query to order by value
     */
    public function scopeOrderedByValue(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('value', $direction);
    }

    /**
     * Query builder for points stats
     */
    public static function points(): Builder
    {
        return static::query()->forStat('points');
    }

    /**
     * Query builder for watchtime stats
     */
    public static function watchtime(): Builder
    {
        return static::query()->forStat('watchtime');
    }

    /**
     * Query builder for top three count stats
     */
    public static function topThreeCount(): Builder
    {
        return static::query()->forStat('topThreeCount');
    }
}
