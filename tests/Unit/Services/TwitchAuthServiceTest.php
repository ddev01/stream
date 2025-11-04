<?php

declare(strict_types=1);

use App\Models\TwitchUser;
use App\Models\TwitchUserStat;
use App\Models\User;
use App\Services\TwitchAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function () {
    \Mockery::close();
});

// Helper to create mock Socialite user for service tests
function createMockSocialiteUser(string $id, string $email, string $displayName): SocialiteUser
{
    $socialiteUser = \Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn($id);
    $socialiteUser->id = $id;
    $socialiteUser->nickname = $displayName;
    $socialiteUser->name = $displayName;
    $socialiteUser->email = $email;
    $socialiteUser->avatar = "https://example.com/{$id}_avatar.png";
    $socialiteUser->user = [
        'display_name' => $displayName,
        'profile_image_url' => "https://example.com/{$id}_avatar.png",
        'broadcaster_type' => 'affiliate',
        'created_at' => '2020-01-01T00:00:00Z',
        'description' => "Description for {$displayName}",
    ];

    return $socialiteUser;
}

test('creates new user and twitch user', function () {
    $service = app(TwitchAuthService::class);
    $socialiteUser = createMockSocialiteUser('123', 'test@example.com', 'TestUser');

    $user = $service->handleOAuthCallback($socialiteUser);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('TestUser');

    expect(TwitchUser::count())->toBe(1);
    $twitchUser = TwitchUser::first();
    expect($twitchUser->twitch_id)->toBe('123')
        ->and($twitchUser->user_id)->toBe($user->id)
        ->and($twitchUser->display_name)->toBe('TestUser')
        ->and($twitchUser->profile_image_url)->toBe('https://example.com/123_avatar.png')
        ->and($twitchUser->broadcaster_type)->toBe('affiliate');
});

test('enriches existing twitch user', function () {
    $service = app(TwitchAuthService::class);

    // Create existing TwitchUser without OAuth data
    $twitchUser = TwitchUser::factory()->create([
        'twitch_id' => '456',
        'display_name' => 'oldname',
        'profile_image_url' => null,
        'user_id' => null,
    ]);

    $socialiteUser = createMockSocialiteUser('456', 'test@example.com', 'NewName');

    $user = $service->handleOAuthCallback($socialiteUser);

    expect($user)->toBeInstanceOf(User::class);
    expect(TwitchUser::count())->toBe(1); // No duplicate

    $twitchUser->refresh();
    expect($twitchUser->user_id)->toBe($user->id)
        ->and($twitchUser->display_name)->toBe('NewName')
        ->and($twitchUser->profile_image_url)->toBe('https://example.com/456_avatar.png')
        ->and($twitchUser->broadcaster_type)->toBe('affiliate');
});

test('links to existing user through twitch user', function () {
    $service = app(TwitchAuthService::class);

    // Create existing User and TwitchUser already linked
    $existingUser = User::factory()->create([
        'name' => 'Existing User',
    ]);

    $existingTwitchUser = TwitchUser::factory()->create([
        'twitch_id' => '789',
        'user_id' => $existingUser->id,
        'display_name' => 'OldName',
    ]);

    $socialiteUser = createMockSocialiteUser('789', 'test@example.com', 'TwitchName');

    $user = $service->handleOAuthCallback($socialiteUser);

    expect($user->id)->toBe($existingUser->id); // Same user
    expect(User::count())->toBe(1); // No duplicate

    $twitchUser = TwitchUser::first();
    expect($twitchUser->user_id)->toBe($existingUser->id)
        ->and($twitchUser->display_name)->toBe('TwitchName');
});

test('preserves existing stats when enriching', function () {
    $service = app(TwitchAuthService::class);

    // Create TwitchUser with stats
    $twitchUser = TwitchUser::factory()->create([
        'twitch_id' => '999',
        'display_name' => 'oldname',
    ]);
    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser->id,
        'name' => 'points',
        'value' => 5000,
    ]);

    $socialiteUser = createMockSocialiteUser('999', 'test@example.com', 'NewName');

    $user = $service->handleOAuthCallback($socialiteUser);

    $twitchUser->refresh();
    expect($twitchUser->stats()->count())->toBe(1);
    expect($twitchUser->stats()->where('name', 'points')->first()->value)->toBe('5000');
});

test('handles null optional fields', function () {
    $service = app(TwitchAuthService::class);

    $socialiteUser = \Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn('111');
    $socialiteUser->id = '111';
    $socialiteUser->nickname = 'MinimalUser';
    $socialiteUser->name = 'MinimalUser';
    $socialiteUser->email = 'minimal@example.com';
    $socialiteUser->avatar = null;
    $socialiteUser->user = [
        'display_name' => 'MinimalUser',
        'profile_image_url' => null,
        'broadcaster_type' => null,
        'created_at' => null,
        'description' => null,
    ];

    $user = $service->handleOAuthCallback($socialiteUser);

    expect($user)->toBeInstanceOf(User::class);

    $twitchUser = TwitchUser::first();
    expect($twitchUser->profile_image_url)->toBeNull()
        ->and($twitchUser->broadcaster_type)->toBeNull()
        ->and($twitchUser->description)->toBeNull();
});

test('updates user name on subsequent logins', function () {
    $service = app(TwitchAuthService::class);

    // First login
    $socialiteUser1 = createMockSocialiteUser('222', 'user@example.com', 'FirstName');
    $user = $service->handleOAuthCallback($socialiteUser1);

    expect($user->name)->toBe('FirstName');

    // Second login with updated name
    $socialiteUser2 = createMockSocialiteUser('222', 'user@example.com', 'UpdatedName');
    $user = $service->handleOAuthCallback($socialiteUser2);

    $twitchUser = TwitchUser::first();
    expect($twitchUser->display_name)->toBe('UpdatedName');
});
