<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'role',
        'subscribed',
        'type',
        'present',
        'last_active',
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
            'subscribed' => 'boolean',
            'present' => 'boolean',
            'last_active' => 'datetime',
            'twitch_created_at' => 'datetime',
            'role' => 'integer',
        ];
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
}
