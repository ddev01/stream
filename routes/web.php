<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwitchAuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\SharedFileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Twitch OAuth routes
Route::get('/auth/twitch', [TwitchAuthController::class, 'redirect']);
Route::get('/auth/twitch/callback', [TwitchAuthController::class, 'callback']);

// Login page - displays Twitch OAuth button
Route::view('/login', 'livewire.auth.login')->name('login');

// Redirect register to login since we only use Twitch OAuth
Route::get('/register', [RegisterController::class, 'redirect']);

// Logout route
Route::post('/logout', [LogoutController::class, 'destroy'])->name('logout');

// Leaderboard routes
Route::get('/leaderboards/points', [LeaderboardController::class, 'points'])->name('leaderboards.points');
Route::get('/leaderboards/watchtime', [LeaderboardController::class, 'watchtime'])->name('leaderboards.watchtime');
Route::get('/leaderboards/top-three', [LeaderboardController::class, 'topThree'])->name('leaderboards.top-three');
Route::get('/leaderboards/gifted-subscriptions', [LeaderboardController::class, 'giftedSubscriptions'])->name('leaderboards.gifted-subscriptions');
Route::get('/leaderboards/trivia-wins', [LeaderboardController::class, 'triviaWins'])->name('leaderboards.trivia-wins');
Route::get('/leaderboards/raiders', [LeaderboardController::class, 'raiders'])->name('leaderboards.raiders');

// Home route
Route::get('/', [HomeController::class, 'index'])->name('home');

// User routes
Route::get('users/{twitchUser}', [UserController::class, 'show'])->name('users.show');

// Public shared file route
Route::get('/share/{token}', [SharedFileController::class, 'show'])->name('shared-files.show');
Route::get('/share/{token}/file', [SharedFileController::class, 'file'])->name('shared-files.file');
Route::get('/share/{token}/download', [SharedFileController::class, 'download'])->name('shared-files.download');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    // Admin routes (admin check is done in the component)
    Route::get('admin/file-sharing', function () {
        return view('admin.file-sharing');
    })->name('admin.file-sharing');
});
