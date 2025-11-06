<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

test('logout destroys session and redirects to home', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect(Auth::check())->toBeTrue();

    $response = $this->post('/logout');

    $response->assertRedirect('/');
    expect(Auth::check())->toBeFalse();
});

test('logout invalidates session', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sessionId = session()->getId();

    $this->post('/logout');

    // Session should be invalidated
    $this->assertGuest();
});

test('logout regenerates CSRF token', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $oldToken = csrf_token();

    $this->post('/logout');

    $newToken = csrf_token();

    expect($newToken)->not->toBe($oldToken);
});
