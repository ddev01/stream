<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');
        $recordAllEntries = config('telescope.record_all_entries', false);

        // If record_all_entries is true, record all entries
        if ($recordAllEntries) {
            return;
        }

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user = null) {
            // Allow all in local environment
            if (app()->environment('local')) {
                return true;
            }

            // Must be authenticated
            if (! $user) {
                \Log::warning('Telescope gate: User not authenticated', [
                    'session_id' => session()->getId(),
                    'auth_check' => auth()->check(),
                ]);

                return false;
            }

            // Must have linked TwitchUser
            $twitchUser = $user->twitchUser ?? null;
            if (! $twitchUser) {
                \Log::warning('Telescope gate: User has no TwitchUser', [
                    'user_id' => $user->id,
                ]);

                return false;
            }

            // Check if Twitch ID is in admin list
            $adminTwitchIds = config('admin.twitch_ids', []);
            $userTwitchId = (string) $twitchUser->twitch_id;
            $isAuthorized = in_array($userTwitchId, $adminTwitchIds, true);

            if (! $isAuthorized) {
                \Log::warning('Telescope gate: User Twitch ID not in admin list', [
                    'user_id' => $user->id,
                    'twitch_id' => $userTwitchId,
                    'admin_ids' => $adminTwitchIds,
                ]);
            }

            return $isAuthorized;
        });
    }
}
