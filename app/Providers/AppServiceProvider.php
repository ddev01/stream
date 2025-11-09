<?php

namespace App\Providers;

use App\Models\TwitchUser;
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
                \Log::warning('Pulse gate: User not authenticated', [
                    'session_id' => session()->getId(),
                    'auth_check' => auth()->check(),
                ]);

                return false;
            }

            // Must have linked TwitchUser
            $twitchUser = $user->twitchUser ?? null;
            if (! $twitchUser) {
                \Log::warning('Pulse gate: User has no TwitchUser', [
                    'user_id' => $user->id,
                ]);

                return false;
            }

            // Check if Twitch ID is in admin list
            $adminTwitchIds = config('admin.twitch_ids', []);
            $userTwitchId = (string) $twitchUser->twitch_id;
            $isAuthorized = in_array($userTwitchId, $adminTwitchIds, true);

            if (! $isAuthorized) {
                \Log::warning('Pulse gate: User Twitch ID not in admin list', [
                    'user_id' => $user->id,
                    'twitch_id' => $userTwitchId,
                    'admin_ids' => $adminTwitchIds,
                ]);
            }

            return $isAuthorized;
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
