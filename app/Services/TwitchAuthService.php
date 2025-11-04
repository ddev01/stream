<?php

namespace App\Services;

use App\Models\TwitchUser;
use App\Models\User;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * Service for handling Twitch OAuth authentication and user management
 */
class TwitchAuthService
{
    /**
     * Handle Twitch OAuth user authentication and linking
     */
    public function handleOAuthCallback(SocialiteUser $twitchOAuthUser): User
    {
        // Find or create the TwitchUser record
        $twitchUser = TwitchUser::firstOrCreate(
            ['twitch_id' => $twitchOAuthUser->id],
            [
                'display_name' => $this->getDisplayName($twitchOAuthUser),
            ]
        );

        // Enrich with OAuth data
        $twitchUser->update([
            'display_name' => $this->getDisplayName($twitchOAuthUser),
            'profile_image_url' => $twitchOAuthUser->user['profile_image_url'] ?? $twitchOAuthUser->avatar,
            'broadcaster_type' => $twitchOAuthUser->user['broadcaster_type'] ?? null,
            'twitch_created_at' => $twitchOAuthUser->user['created_at'] ?? null,
            'description' => $twitchOAuthUser->user['description'] ?? null,
        ]);

        // Find or create the Laravel User based on the linked TwitchUser
        if ($twitchUser->user_id) {
            // TwitchUser already linked to a User
            $user = User::find($twitchUser->user_id);
        } else {
            // Check if there's an authenticated User who should be linked
            // (e.g., User posted stats via API, creating this TwitchUser)
            $authenticatedUser = auth()->user();

            if ($authenticatedUser && ! $authenticatedUser->twitchUser) {
                // Link the TwitchUser to the authenticated User
                $twitchUser->user_id = $authenticatedUser->id;
                $twitchUser->save();
                $user = $authenticatedUser;
            } else {
                // Create a new User and link it to the TwitchUser
                $user = User::create([
                    'name' => $this->getDisplayName($twitchOAuthUser),
                ]);
                $twitchUser->user_id = $user->id;
                $twitchUser->save();
            }
        }

        return $user;
    }

    /**
     * Extract display name from Socialite user object
     */
    private function getDisplayName(SocialiteUser $twitchOAuthUser): string
    {
        return $twitchOAuthUser->user['display_name'] ?? $twitchOAuthUser->name;
    }
}
