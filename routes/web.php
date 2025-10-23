<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Laravel\Socialite\Facades\Socialite;
use Livewire\Volt\Volt;

// Twitch OAuth routes
Route::get('/auth/twitch', function () {
    return Socialite::driver('twitch')->redirect();
});

Route::get('/auth/twitch/callback', function () {
    try {
        $twitchOAuthUser = Socialite::driver('twitch')->user();

        // Find or create the TwitchUser record
        $twitchUser = \App\Models\TwitchUser::firstOrCreate(
            ['twitch_id' => $twitchOAuthUser->id],
            [
                'name' => $twitchOAuthUser->nickname,
                'display_name' => $twitchOAuthUser->user['display_name'] ?? $twitchOAuthUser->name,
                'type' => 'twitch',
            ]
        );

        // Enrich with OAuth data
        $twitchUser->update([
            'name' => $twitchOAuthUser->nickname,
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

    } catch (\Exception $e) {
        throw $e; // User requested to keep this debug statement
    }
});

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
