<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    // Home route is publicly accessible
    $response->assertStatus(200);
});
