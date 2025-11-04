<?php

declare(strict_types=1);

use App\Models\TwitchUser;
use App\Models\TwitchUserStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

test('twitch oauth creates user and links twitch user', function () {
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('112699727');
    $socialiteUser->id = '112699727';
    $socialiteUser->nickname = 'mychoppaeats';
    $socialiteUser->name = 'mychoppaeats';
    $socialiteUser->email = 'test@example.com';
    $socialiteUser->avatar = 'https://example.com/avatar.png';
    $socialiteUser->user = [
        'display_name' => 'mychoppaeats',
        'profile_image_url' => 'https://example.com/avatar.png',
        'broadcaster_type' => 'affiliate',
        'created_at' => '2016-01-14T08:51:48Z',
        'description' => 'Test description',
    ];

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get('/auth/twitch/callback');

    $response->assertRedirect('/dashboard');

    expect(User::count())->toBe(1)
        ->and(TwitchUser::count())->toBe(1);

    $user = User::first();
    $twitchUser = TwitchUser::first();

    expect($user->name)->toBe('mychoppaeats')
        ->and($twitchUser->twitch_id)->toBe('112699727')
        ->and($twitchUser->user_id)->toBe($user->id)
        ->and($twitchUser->profile_image_url)->toBe('https://example.com/avatar.png')
        ->and($twitchUser->broadcaster_type)->toBe('affiliate');
});

test('twitch oauth enriches existing twitch user with stats', function () {
    // Create a Twitch user with stats (from C# import)
    $twitchUser = TwitchUser::factory()->create([
        'twitch_id' => '112699727',
        'display_name' => 'mychoppaeats',
        'profile_image_url' => null,
        'user_id' => null,
    ]);

    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser->id,
        'name' => 'points',
        'value' => 5000,
    ]);

    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser->id,
        'name' => 'watchtime',
        'value' => 1000000,
    ]);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('112699727');
    $socialiteUser->id = '112699727';
    $socialiteUser->nickname = 'mychoppaeats';
    $socialiteUser->name = 'mychoppaeats';
    $socialiteUser->email = 'test@example.com';
    $socialiteUser->avatar = 'https://example.com/avatar.png';
    $socialiteUser->user = [
        'display_name' => 'mychoppaeats',
        'profile_image_url' => 'https://example.com/avatar.png',
        'broadcaster_type' => 'partner',
        'created_at' => '2016-01-14T08:51:48Z',
        'description' => 'Awesome streamer',
    ];

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get('/auth/twitch/callback');

    $response->assertRedirect('/dashboard');

    // Should still have 1 TwitchUser (enriched, not duplicated)
    expect(TwitchUser::count())->toBe(1)
        ->and(User::count())->toBe(1);

    $twitchUser->refresh();
    $user = User::first();

    expect($twitchUser->user_id)->toBe($user->id)
        ->and($twitchUser->profile_image_url)->toBe('https://example.com/avatar.png')
        ->and($twitchUser->broadcaster_type)->toBe('partner')
        ->and($twitchUser->description)->toBe('Awesome streamer')
        ->and($twitchUser->stats()->count())->toBe(2);
});

test('user can access stats through relationship', function () {
    $twitchUser = TwitchUser::factory()->create([
        'twitch_id' => '112699727',
    ]);

    $user = User::factory()->create();
    $twitchUser->user_id = $user->id;
    $twitchUser->save();

    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser->id,
        'name' => 'points',
        'value' => 9999,
    ]);

    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser->id,
        'name' => 'watchtime',
        'value' => 555555,
    ]);

    $this->actingAs($user);

    expect($user->stat('points'))->toBe('9999')
        ->and($user->stat('watchtime'))->toBe('555555')
        ->and($user->stat('nonexistent'))->toBeNull();
});

test('twitch oauth links to existing user through twitch user', function () {
    // User and TwitchUser already exist and are linked
    $existingUser = User::factory()->create([
        'name' => 'Existing User',
    ]);

    $existingTwitchUser = TwitchUser::factory()->create([
        'twitch_id' => '112699727',
        'user_id' => $existingUser->id,
        'display_name' => 'OldName',
    ]);

    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('112699727');
    $socialiteUser->id = '112699727';
    $socialiteUser->nickname = 'mychoppaeats';
    $socialiteUser->name = 'mychoppaeats';
    $socialiteUser->email = 'test@example.com';
    $socialiteUser->avatar = 'https://example.com/avatar.png';
    $socialiteUser->user = [
        'display_name' => 'mychoppaeats',
        'profile_image_url' => 'https://example.com/avatar.png',
        'broadcaster_type' => 'affiliate',
        'created_at' => '2016-01-14T08:51:48Z',
        'description' => 'Test description',
    ];

    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

    $response = $this->get('/auth/twitch/callback');

    $response->assertRedirect('/dashboard');

    // Should not create a new user
    expect(User::count())->toBe(1);

    $twitchUser = TwitchUser::first();
    expect($twitchUser->user_id)->toBe($existingUser->id)
        ->and($twitchUser->display_name)->toBe('mychoppaeats');
});

test('twitch oauth redirect route returns socialite redirect', function () {
    \Laravel\Socialite\Facades\Socialite::shouldReceive('driver->redirect')->andReturn(redirect('https://twitch.tv/oauth/authorize'));
    $this->get('/auth/twitch')->assertRedirect();
});
