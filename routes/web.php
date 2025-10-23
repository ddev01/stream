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
        $twitchUser = Socialite::driver('twitch')->user();
        // No user by Twitch or email? Create a new one!
        $user = \App\Models\User::create([
            'name' => $twitchUser->name,
            'email' => $twitchUser->email,
            'twitch_id' => $twitchUser->id,
            'twitch_username' => $twitchUser->nickname,
            'twitch_display_name' => $twitchUser->name,
            'twitch_avatar' => $twitchUser->avatar,
        ]);

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
