<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            // Allow all in local environment
            if (app()->environment('local')) {
                return true;
            }

            // Must be authenticated
            if (! $user) {
                return false;
            }

            // Must have linked TwitchUser
            $twitchUser = $user->twitchUser;
            if (! $twitchUser) {
                return false;
            }

            // Check if Twitch ID is in admin list
            $adminTwitchIds = config('admin.twitch_ids', []);
            $userTwitchId = (string) $twitchUser->twitch_id;

            return in_array($userTwitchId, $adminTwitchIds, true);
        });
    }
}
