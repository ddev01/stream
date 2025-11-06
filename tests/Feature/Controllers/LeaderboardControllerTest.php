<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('points leaderboard page is accessible', function () {
    $response = $this->get(route('leaderboards.points'));

    $response->assertStatus(200);
});

test('watchtime leaderboard page is accessible', function () {
    $response = $this->get(route('leaderboards.watchtime'));

    $response->assertStatus(200);
});

test('top three leaderboard page is accessible', function () {
    $response = $this->get(route('leaderboards.top-three'));

    $response->assertStatus(200);
});
