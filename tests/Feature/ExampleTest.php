<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    // Home route redirects to dashboard, which requires auth, so guests get redirected
    $response->assertRedirect(route('dashboard'));
});
