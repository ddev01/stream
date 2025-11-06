<?php

declare(strict_types=1);

use App\Models\TwitchUser;
use App\Models\TwitchUserStat;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

test('setStat creates new stat when it does not exist', function () {
    $twitchUser = TwitchUser::factory()->create();

    $stat = $twitchUser->setStat('points', 100);

    expect($stat)->toBeInstanceOf(TwitchUserStat::class)
        ->and($stat->name)->toBe('points')
        ->and($stat->value)->toBe(100)
        ->and($twitchUser->stats()->count())->toBe(1);
});

test('setStat updates existing stat', function () {
    $twitchUser = TwitchUser::factory()->create();
    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser->id,
        'name' => 'points',
        'value' => 50,
    ]);

    $stat = $twitchUser->setStat('points', 200);

    expect($stat->value)->toBe(200)
        ->and($twitchUser->stats()->where('name', 'points')->count())->toBe(1);
});

test('setStat uses custom lastWrite when provided', function () {
    $twitchUser = TwitchUser::factory()->create();
    $customTime = now()->subDays(5);

    $stat = $twitchUser->setStat('points', 100, $customTime);

    expect($stat->last_write->format('Y-m-d H:i:s'))->toBe($customTime->format('Y-m-d H:i:s'));
});

test('setStat uses current time when lastWrite is not provided', function () {
    $twitchUser = TwitchUser::factory()->create();

    $stat = $twitchUser->setStat('points', 100);

    expect($stat->last_write)->not->toBeNull()
        ->and($stat->last_write->isToday())->toBeTrue();
});
