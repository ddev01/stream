<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Represents a Twitch user identity, which may or may not be linked to a Laravel app user
 */
class TwitchUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'twitch_id',
        'user_id',
        'display_name',
        'profile_image_url',
        'broadcaster_type',
        'description',
        'twitch_created_at',
        'email',
    ];

    /**
     * Get the attributes that should be cast
     */
    protected function casts(): array
    {
        return [
            'twitch_created_at' => 'datetime',
        ];
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName(): string
    {
        return 'twitch_id';
    }

    /**
     * Get the user's initials from display name
     */
    public function initials(): string
    {
        $name = $this->display_name ?? '';

        return Str::of($name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the Laravel app user associated with this Twitch user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all stats for this Twitch user
     */
    public function stats(): HasMany
    {
        return $this->hasMany(TwitchUserStat::class);
    }

    /**
     * Get a specific stat value by name
     */
    public function getStat(string $name): mixed
    {
        return $this->stats()
            ->where('name', $name)
            ->value('value');
    }

    /**
     * Set or update a stat for this Twitch user
     */
    public function setStat(string $name, mixed $value, ?\DateTime $lastWrite = null): TwitchUserStat
    {
        return $this->stats()->updateOrCreate(
            ['name' => $name],
            [
                'value' => $value,
                'last_write' => $lastWrite ?? now(),
            ]
        );
    }

    /**
     * Get the count of gifted subscriptions for this Twitch user
     */
    public function getGiftedSubsCount(): int
    {
        return \App\Models\SubscriptionHistory::where('gifter_user_id', $this->twitch_id)->count();
    }

    /**
     * Get the count of raids for this Twitch user
     */
    public function getRaidsCount(): int
    {
        return \App\Models\RaidHistory::where('user_id', $this->twitch_id)->count();
    }
}
