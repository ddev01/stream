<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TwitchUser;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class TwitchAuthController extends Controller
{
    /**
     * Redirect to Twitch OAuth provider
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('twitch')->redirect();
    }

    /**
     * Handle Twitch OAuth callback
     */
    public function callback(): RedirectResponse
    {
        try {
            $twitchOAuthUser = Socialite::driver('twitch')->user();

            // Find or create the TwitchUser record
            $twitchUser = TwitchUser::firstOrCreate(
                ['twitch_id' => $twitchOAuthUser->id],
                [
                    'display_name' => $twitchOAuthUser->user['display_name'] ?? $twitchOAuthUser->name,
                ]
            );

            // Enrich with OAuth data
            $twitchUser->update([
                'display_name' => $twitchOAuthUser->user['display_name'] ?? $twitchOAuthUser->name,
                'profile_image_url' => $twitchOAuthUser->user['profile_image_url'] ?? $twitchOAuthUser->avatar,
                'broadcaster_type' => $twitchOAuthUser->user['broadcaster_type'] ?? null,
                'twitch_created_at' => $twitchOAuthUser->user['created_at'] ?? null,
                'description' => $twitchOAuthUser->user['description'] ?? null,
                'email' => $twitchOAuthUser->email,
            ]);

            // Find or create the Laravel User
            $user = User::firstOrCreate(
                ['email' => $twitchOAuthUser->email],
                [
                    'name' => $twitchOAuthUser->user['display_name'] ?? $twitchOAuthUser->name,
                ]
            );

            // Link TwitchUser to User
            $twitchUser->user_id = $user->id;
            $twitchUser->save();

            Auth::login($user);

            return redirect('/dashboard');

        } catch (Exception $e) {
            // Log the error for debugging
            logger()->error('Twitch OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Redirect to login with error message
            return redirect('/login')->with('error', 'Authentication failed. Please try again.');
        }
    }
}