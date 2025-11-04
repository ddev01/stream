<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('login page can be rendered', function () {
    $response = $this->get(route('login'));
    $response->assertStatus(200);
    $response->assertSee('Sign in with Twitch');
});

test('guests can visit the home page', function () {
    $response = $this->get(route('home'));
    $response->assertStatus(200);
});

test('authenticated users can visit the home', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('home'));
    $response->assertStatus(200);
});
