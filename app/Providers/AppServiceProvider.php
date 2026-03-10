<?php

namespace App\Providers;

use App\Models\TwitchUser;
use App\Services\TwitchAuthService;
use App\Services\TwitchStatsService;
use Illuminate\Support\Facades\Event;
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

        // Ensure fake Laravel user exists for system operations
        $this->ensureFakeLaravelUser();
    }

    /**
     * Ensure the fake Laravel user exists in twitch_users table
     * This runs on every application boot to guarantee the user exists
     */
    private function ensureFakeLaravelUser(): void
    {
        try {
            TwitchUser::firstOrCreate(
                ['twitch_id' => '0'],
                ['display_name' => 'fake_laravel_user']
            );
        } catch (\Exception $e) {
            // Silently fail if table doesn't exist yet (during migrations)
            // or if there's any other issue
        }
    }
}
