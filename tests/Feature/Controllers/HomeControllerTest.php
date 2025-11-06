<?php

declare(strict_types=1);

use App\Models\TwitchUser;
use App\Models\TwitchUserStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('home page displays top stats for guests', function () {
    // Create some stats
    $twitchUser1 = TwitchUser::factory()->create();
    $twitchUser2 = TwitchUser::factory()->create();

    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser1->id,
        'name' => 'points',
        'value' => 1000,
    ]);

    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser2->id,
        'name' => 'points',
        'value' => 500,
    ]);

    $response = $this->get(route('home'));

    $response->assertStatus(200)
        ->assertSee('Top 5 Points');
});

test('home page displays user stats for authenticated users', function () {
    $user = User::factory()->create();
    $twitchUser = TwitchUser::factory()->create(['user_id' => $user->id]);

    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser->id,
        'name' => 'points',
        'value' => 750,
    ]);

    $response = $this->actingAs($user)->get(route('home'));

    $response->assertStatus(200)
        ->assertSee('Your Stats');
});
