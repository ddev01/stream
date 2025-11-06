<?php

declare(strict_types=1);

use App\Models\TwitchUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user show page displays user profile', function () {
    $user = User::factory()->create(['name' => 'Test User']);
    $twitchUser = TwitchUser::factory()->create(['user_id' => $user->id]);

    $response = $this->get(route('users.show', $user));

    $response->assertStatus(200);
    $response->assertSee('Test User', false);
});

test('user show page works for guests', function () {
    $user = User::factory()->create();

    $response = $this->get(route('users.show', $user));

    $response->assertStatus(200);
});

test('user show page returns 404 for non-existent user', function () {
    $response = $this->get(route('users.show', 99999));

    $response->assertNotFound();
});
