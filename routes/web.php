<?php

use App\Http\Controllers\Auth\TwitchAuthController;
use App\Http\Controllers\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Twitch OAuth routes
Route::get('/auth/twitch', [TwitchAuthController::class, 'redirect']);
Route::get('/auth/twitch/callback', [TwitchAuthController::class, 'callback']);

// Login page - displays Twitch OAuth button
Route::view('/login', 'livewire.auth.login')->name('login');

// Redirect register to login since we only use Twitch OAuth
Route::get('/register', function () {
    return redirect('/login');
});

// Logout route
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->name('logout');

Route::get('/leaderboards/points', function () {
    return view('points-leaderboard');
})->name('leaderboards.points');

Route::get('/leaderboards/watchtime', function () {
    return view('watchtime-leaderboard');
})->name('leaderboards.watchtime');

Route::get('/leaderboards/top-three', function () {
    return view('top-three-leaderboard');
})->name('leaderboards.top-three');

Route::get('/', [HomeController::class, 'index'])
    ->name('home');

Route::get('users/{user}', function (App\Models\User $user) {
    return view('users.show', ['user' => $user]);
})->name('users.show');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');
});
