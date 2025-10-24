<?php

declare(strict_types=1);

use App\Models\TwitchUser;
use App\Models\TwitchUserStat;
use App\Models\User;

uses(Tests\TestCase::class);
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('twitch user belongs to user', function () {
    $user = User::factory()->create();
    $tw = TwitchUser::factory()->create(['user_id' => $user->id]);
    expect($tw->user)->toBeInstanceOf(User::class);
});

test('twitch user has many stats', function () {
    $tw = TwitchUser::factory()->create();
    $stat = TwitchUserStat::factory()->create(['twitch_user_id' => $tw->id]);
    expect($tw->stats()->count())->toBe(1);
    expect($tw->stats->first()->id)->toBe($stat->id);
});

test('getStat returns correct value', function () {
    $tw = TwitchUser::factory()->create();
    TwitchUserStat::factory()->create(['twitch_user_id' => $tw->id, 'name' => 'points', 'value' => 99]);
    expect($tw->getStat('points'))->toBe('99');
});

test('getStat returns null for nonexistent stat', function () {
    $tw = TwitchUser::factory()->create();
    expect($tw->getStat('nonsense'))->toBeNull();
});

test('fillable attributes are correct', function () {
    $expected = [
        'twitch_id', 'user_id', 'display_name', 'profile_image_url', 'broadcaster_type', 'description', 'twitch_created_at', 'email',
    ];
    expect((new TwitchUser)->getFillable())->toMatchArray($expected);
});

test('casts are applied correctly', function () {
    $tw = TwitchUser::factory()->enriched()->create();
    expect($tw->twitch_created_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});
