<?php

declare(strict_types=1);

use App\Models\TwitchUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user show page displays user profile', function () {
    $user = User::factory()->create(['name' => 'Test User']);
    $twitchUser = TwitchUser::factory()->create([
        'user_id' => $user->id,
        'display_name' => 'Test User',
    ]);

    $response = $this->get(route('users.show', $twitchUser->twitch_id));

    $response->assertStatus(200);
    $response->assertSee('Test User', false);
});

test('user show page works for guests', function () {
    $twitchUser = TwitchUser::factory()->create(['display_name' => 'Guest User']);

    $response = $this->get(route('users.show', $twitchUser->twitch_id));

    $response->assertStatus(200);
});

test('user show page returns 404 for non-existent user', function () {
    $response = $this->get(route('users.show', 'non-existent-twitch-id'));

    $response->assertNotFound();
});
