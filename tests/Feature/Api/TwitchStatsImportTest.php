<?php

declare(strict_types=1);

use App\Models\TwitchUser;
use App\Models\TwitchUserStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// Helper function to create authenticated user and token
function createAuthenticatedUser(): array
{
    $user = User::factory()->create([
        'name' => 'Test API User',
        'email' => 'api@test.local',
        'password' => Hash::make('password'),
    ]);

    $token = $user->createToken('test-token', ['*']);

    return [
        'user' => $user,
        'token' => $token->plainTextToken,
    ];
}

test('can bulk import twitch stats', function () {
    $auth = createAuthenticatedUser();
    
    $stats = [
        [
            'userId' => '112699727',
            'platform' => 'twitch',
            'name' => 'points',
            'value' => 3000,
            'lastWrite' => '2025-10-21T18:30:44.8350000Z',
        ],
        [
            'userId' => '112699727',
            'platform' => 'twitch',
            'name' => 'watchtime',
            'value' => 1314780,
            'lastWrite' => '2025-07-18T23:49:25.5890000Z',
        ],
        [
            'userId' => '123456789',
            'platform' => 'twitch',
            'name' => 'points',
            'value' => 500,
            'lastWrite' => '2025-10-20T12:00:00.0000000Z',
        ],
    ];

    $response = $this->postJson('/api/twitch/stats', ['stats' => $stats], [
        'Authorization' => 'Bearer ' . $auth['token'],
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'status' => 'success',
            'imported' => 3,
            'updated' => 0,
            'total' => 3,
        ]);

    expect(TwitchUser::count())->toBe(2)
        ->and(TwitchUserStat::count())->toBe(3);

    $twitchUser = TwitchUser::where('twitch_id', '112699727')->first();
    expect($twitchUser)->not->toBeNull();
    expect($twitchUser->stats()->count())->toBe(2);

    $pointsStat = $twitchUser->stats()->where('name', 'points')->first();
    expect($pointsStat->value)->toBe('3000');
});

test('can update existing stats', function () {
    $auth = createAuthenticatedUser();
    
    $twitchUser = TwitchUser::factory()->create(['twitch_id' => '112699727']);
    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser->id,
        'name' => 'points',
        'value' => 1000,
    ]);

    $stats = [
        [
            'userId' => '112699727',
            'platform' => 'twitch',
            'name' => 'points',
            'value' => 5000,
            'lastWrite' => '2025-10-23T12:00:00.0000000Z',
        ],
    ];

    $response = $this->postJson('/api/twitch/stats', ['stats' => $stats], [
        'Authorization' => 'Bearer ' . $auth['token'],
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'status' => 'success',
            'imported' => 0,
            'updated' => 1,
            'total' => 1,
        ]);

    $pointsStat = $twitchUser->stats()->where('name', 'points')->first();
    expect($pointsStat->value)->toBe('5000');
});

test('creates twitch user if not exists when importing stats', function () {
    $auth = createAuthenticatedUser();
    
    expect(TwitchUser::count())->toBe(0);

    $stats = [
        [
            'userId' => '999999999',
            'platform' => 'twitch',
            'name' => 'points',
            'value' => 100,
        ],
    ];

    $response = $this->postJson('/api/twitch/stats', ['stats' => $stats], [
        'Authorization' => 'Bearer ' . $auth['token'],
    ]);

    $response->assertSuccessful();

    expect(TwitchUser::count())->toBe(1);
    $twitchUser = TwitchUser::where('twitch_id', '999999999')->first();
    expect($twitchUser)->not->toBeNull()
        ->and($twitchUser->stats()->count())->toBe(1);
});

test('validates required fields for stats import', function () {
    $auth = createAuthenticatedUser();
    
    $stats = [
        [
            'userId' => '112699727',
            // Missing 'name' and 'value'
        ],
    ];

    $response = $this->postJson('/api/twitch/stats', ['stats' => $stats], [
        'Authorization' => 'Bearer ' . $auth['token'],
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'status' => 'error',
            'message' => 'Validation failed',
        ]);
});

test('handles json values in stats', function () {
    $auth = createAuthenticatedUser();
    
    $stats = [
        [
            'userId' => '112699727',
            'platform' => 'twitch',
            'name' => 'complexData',
            'value' => ['nested' => 'data', 'count' => 42],
            'lastWrite' => '2025-10-23T12:00:00.0000000Z',
        ],
    ];

    $response = $this->postJson('/api/twitch/stats', ['stats' => $stats], [
        'Authorization' => 'Bearer ' . $auth['token'],
    ]);

    $response->assertSuccessful();

    $twitchUser = TwitchUser::where('twitch_id', '112699727')->first();
    $stat = $twitchUser->stats()->where('name', 'complexData')->first();

    $decoded = json_decode($stat->value, true);
    expect($decoded)->toBeArray()
        ->and($decoded['nested'])->toBe('data')
        ->and($decoded['count'])->toBe(42);
});

test('requires stats array', function () {
    $auth = createAuthenticatedUser();
    
    $response = $this->postJson('/api/twitch/stats', [], [
        'Authorization' => 'Bearer ' . $auth['token'],
    ]);

    $response->assertStatus(422);
});
