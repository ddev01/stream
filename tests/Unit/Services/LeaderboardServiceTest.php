<?php

declare(strict_types=1);

use App\Models\TwitchUser;
use App\Models\TwitchUserStat;
use App\Models\User;
use App\Services\LeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

test('getTopStats returns top stats for a given stat name', function () {
    $service = app(LeaderboardService::class);

    // Create multiple users with different point values
    $twitchUser1 = TwitchUser::factory()->create();
    $twitchUser2 = TwitchUser::factory()->create();
    $twitchUser3 = TwitchUser::factory()->create();

    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser1->id,
        'name' => 'points',
        'value' => 100,
    ]);

    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser2->id,
        'name' => 'points',
        'value' => 200,
    ]);

    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser3->id,
        'name' => 'points',
        'value' => 50,
    ]);

    $topStats = $service->getTopStats('points', 2);

    expect($topStats)->toHaveCount(2)
        ->and($topStats->first()->value)->toBe(200)
        ->and($topStats->last()->value)->toBe(100);
});

test('getTopStats respects limit parameter', function () {
    $service = app(LeaderboardService::class);

    // Create 10 different twitch users with different point values
    for ($i = 1; $i <= 10; $i++) {
        $twitchUser = TwitchUser::factory()->create();
        TwitchUserStat::factory()->create([
            'twitch_user_id' => $twitchUser->id,
            'name' => 'points',
            'value' => $i * 10,
        ]);
    }

    $topStats = $service->getTopStats('points', 5);

    expect($topStats)->toHaveCount(5);
});

test('getUserStatAndPosition returns stat and position for authenticated user', function () {
    $service = app(LeaderboardService::class);

    $user = User::factory()->create();
    $twitchUser = TwitchUser::factory()->create(['user_id' => $user->id]);

    // Create stats with different values
    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser->id,
        'name' => 'points',
        'value' => 100,
    ]);

    // Create other users with higher values
    $otherTwitchUser1 = TwitchUser::factory()->create();
    $otherTwitchUser2 = TwitchUser::factory()->create();

    TwitchUserStat::factory()->create([
        'twitch_user_id' => $otherTwitchUser1->id,
        'name' => 'points',
        'value' => 200,
    ]);

    TwitchUserStat::factory()->create([
        'twitch_user_id' => $otherTwitchUser2->id,
        'name' => 'points',
        'value' => 150,
    ]);

    $result = $service->getUserStatAndPosition($user, 'points');

    expect($result['stat'])->not->toBeNull()
        ->and($result['stat']->value)->toBe(100)
        ->and($result['position'])->toBe(3); // 2 users have higher values
});

test('getUserStatAndPosition returns null when user has no twitch user', function () {
    $service = app(LeaderboardService::class);

    $user = User::factory()->create();

    $result = $service->getUserStatAndPosition($user, 'points');

    expect($result['stat'])->toBeNull()
        ->and($result['position'])->toBeNull();
});

test('getUserStatAndPosition returns null when user has no stat', function () {
    $service = app(LeaderboardService::class);

    $user = User::factory()->create();
    $twitchUser = TwitchUser::factory()->create(['user_id' => $user->id]);
    // No stats created

    $result = $service->getUserStatAndPosition($user, 'points');

    expect($result['stat'])->toBeNull()
        ->and($result['position'])->toBeNull();
});

test('getUserStatAndPosition returns position 1 when user has highest value', function () {
    $service = app(LeaderboardService::class);

    $user = User::factory()->create();
    $twitchUser = TwitchUser::factory()->create(['user_id' => $user->id]);

    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser->id,
        'name' => 'points',
        'value' => 500,
    ]);

    // Create other users with lower values
    $otherTwitchUser = TwitchUser::factory()->create();
    TwitchUserStat::factory()->create([
        'twitch_user_id' => $otherTwitchUser->id,
        'name' => 'points',
        'value' => 100,
    ]);

    $result = $service->getUserStatAndPosition($user, 'points');

    expect($result['position'])->toBe(1);
});
