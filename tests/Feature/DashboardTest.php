<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('login page can be rendered', function () {
    $response = $this->get(route('login'));
    $response->assertStatus(200);
    $response->assertSee('Sign in with Twitch');
});

test('guests are redirected to login', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});
