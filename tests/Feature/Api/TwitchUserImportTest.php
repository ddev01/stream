<?php

declare(strict_types=1);

use App\Models\TwitchUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can bulk import twitch users', function () {
    $users = [
        [
            'id' => '112699727',
            'name' => 'mychoppaeats',
            'display' => 'mychoppaeats',
            'role' => 4,
            'subscribed' => true,
            'type' => 'twitch',
            'present' => true,
            'lastActive' => '2025-10-24T01:00:00.1220985+02:00',
        ],
        [
            'id' => '123456789',
            'name' => 'testuser',
            'display' => 'TestUser',
            'role' => 2,
            'subscribed' => false,
            'type' => 'twitch',
            'present' => false,
            'lastActive' => '2025-10-23T12:00:00.0000000+02:00',
        ],
    ];

    $response = $this->postJson('/api/twitch/users', ['users' => $users]);

    $response->assertSuccessful()
        ->assertJson([
            'status' => 'success',
            'imported' => 2,
            'updated' => 0,
            'total' => 2,
        ]);

    expect(TwitchUser::count())->toBe(2);

    $twitchUser = TwitchUser::where('twitch_id', '112699727')->first();
    expect($twitchUser->name)->toBe('mychoppaeats')
        ->and($twitchUser->display_name)->toBe('mychoppaeats')
        ->and($twitchUser->role)->toBe(4)
        ->and($twitchUser->subscribed)->toBeTrue();
});

test('can update existing twitch users', function () {
    $twitchUser = TwitchUser::factory()->create([
        'twitch_id' => '112699727',
        'name' => 'oldname',
        'display_name' => 'OldName',
    ]);

    $users = [
        [
            'id' => '112699727',
            'name' => 'newname',
            'display' => 'NewName',
            'role' => 4,
            'subscribed' => true,
            'type' => 'twitch',
            'present' => true,
        ],
    ];

    $response = $this->postJson('/api/twitch/users', ['users' => $users]);

    $response->assertSuccessful()
        ->assertJson([
            'status' => 'success',
            'imported' => 0,
            'updated' => 1,
            'total' => 1,
        ]);

    $twitchUser->refresh();
    expect($twitchUser->name)->toBe('newname')
        ->and($twitchUser->display_name)->toBe('NewName');
});

test('validates required fields for user import', function () {
    $users = [
        [
            'name' => 'testuser',
            // Missing 'id' field
        ],
    ];

    $response = $this->postJson('/api/twitch/users', ['users' => $users]);

    $response->assertStatus(422)
        ->assertJson([
            'status' => 'error',
            'message' => 'Validation failed',
        ]);
});

test('handles empty user array gracefully', function () {
    // Empty arrays pass validation but do nothing
    $response = $this->postJson('/api/twitch/users', ['users' => []]);

    // Laravel validation treats empty array as missing, so we expect 422
    $response->assertStatus(422);

    expect(TwitchUser::count())->toBe(0);
});

test('requires users array', function () {
    $response = $this->postJson('/api/twitch/users', []);

    $response->assertStatus(422);
});
