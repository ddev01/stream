<?php

namespace App\Providers;

use App\Services\TwitchAuthService;
use App\Services\TwitchStatsService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register services as singletons for better performance
        $this->app->singleton(TwitchStatsService::class);
        $this->app->singleton(TwitchAuthService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('twitch', \SocialiteProviders\Twitch\Provider::class);
        });

        // Register Pulse authorization gate
        Gate::define('viewPulse', function ($user = null) {
            // Allow all in local environment
            if (app()->environment('local')) {
                return true;
            }

            // Must be authenticated
            if (! $user) {
                return false;
            }

            // Must have linked TwitchUser
            $twitchUser = $user->twitchUser ?? null;
            if (! $twitchUser) {
                return false;
            }

            // Check if Twitch ID is in admin list
            // Convert to string and trim whitespace for proper comparison
            $adminTwitchIds = array_map(
                fn ($id) => trim((string) $id),
                array_filter(
                    explode(',', env('ADMIN_TWITCH_IDS', ''))
                )
            );

            $userTwitchId = (string) $twitchUser->twitch_id;

            return in_array($userTwitchId, $adminTwitchIds, true);
        });
    }
}
