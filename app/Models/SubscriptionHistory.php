<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Subscription history from Twitch
 */
class SubscriptionHistory extends Model
{
    protected $table = 'subscription_history';

    protected $fillable = [
        'oid',
        'user_id',
        'gifter_user_id',
        'subscribed_at',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
    ];

    /**
     * Get the user who received the subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(TwitchUser::class, 'user_id', 'twitch_id');
    }

    /**
     * Get the user who gifted the subscription
     */
    public function gifter(): BelongsTo
    {
        return $this->belongsTo(TwitchUser::class, 'gifter_user_id', 'twitch_id');
    }
}
