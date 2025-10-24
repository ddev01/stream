<?php

declare(strict_types=1);

use App\Models\TwitchUser;
use App\Models\TwitchUserStat;
use App\Models\User;

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('user has one twitch user', function () {
    $user = User::factory()->create();
    $tw = TwitchUser::factory()->create(['user_id' => $user->id]);
    expect($user->twitchUser)->toBeInstanceOf(TwitchUser::class);
});

test('stat helper method returns correct value', function () {
    $user = User::factory()->create();
    $tw = TwitchUser::factory()->create(['user_id' => $user->id]);
    TwitchUserStat::factory()->create(['twitch_user_id' => $tw->id, 'name' => 'points', 'value' => 555]);
    expect($user->stat('points'))->toBe('555');
});

test('stat helper returns null when no twitch user', function () {
    $user = User::factory()->create();
    expect($user->stat('nope'))->toBeNull();
});

test('stat helper method returns null for nonexistent stat', function () {
    $user = User::factory()->create();
    $tw = TwitchUser::factory()->create(['user_id' => $user->id]);
    expect($user->stat('missing'))->toBeNull();
});
