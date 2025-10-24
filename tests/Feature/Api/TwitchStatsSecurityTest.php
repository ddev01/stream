<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

test('twitch stats endpoint requires authentication', function () {
    $response = $this->postJson('/api/twitch/stats', [
        'stats' => [
            [
                'userId' => '123456789',
                'userName' => 'testuser',
                'platform' => 'twitch',
                'name' => 'points',
                'value' => 100,
                'lastWrite' => now()->toISOString(),
            ],
        ],
    ]);

    $response->assertUnauthorized();
    $response->assertJson(['message' => 'Unauthenticated.']);
});

test('twitch stats endpoint accepts valid token', function () {
    // Create a user and generate a token
    $user = User::factory()->create([
        'name' => 'Test API User',
        'email' => 'api@test.local',
        'password' => Hash::make('password'),
    ]);

    $token = $user->createToken('test-token', ['*']);

    $response = $this->postJson('/api/twitch/stats', [
        'stats' => [
            [
                'userId' => '123456789',
                'userName' => 'testuser',
                'platform' => 'twitch',
                'name' => 'points',
                'value' => 100,
                'lastWrite' => now()->toISOString(),
            ],
        ],
    ], [
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'status' => 'success',
        'message' => 'Twitch stats imported successfully',
    ]);
});

test('twitch stats endpoint rejects invalid token', function () {
    $response = $this->postJson('/api/twitch/stats', [
        'stats' => [
            [
                'userId' => '123456789',
                'userName' => 'testuser',
                'platform' => 'twitch',
                'name' => 'points',
                'value' => 100,
                'lastWrite' => now()->toISOString(),
            ],
        ],
    ], [
        'Authorization' => 'Bearer invalid-token-12345',
    ]);

    $response->assertUnauthorized();
    $response->assertJson(['message' => 'Unauthenticated.']);
});

test('twitch stats endpoint rejects malformed authorization header', function () {
    $response = $this->postJson('/api/twitch/stats', [
        'stats' => [
            [
                'userId' => '123456789',
                'userName' => 'testuser',
                'platform' => 'twitch',
                'name' => 'points',
                'value' => 100,
                'lastWrite' => now()->toISOString(),
            ],
        ],
    ], [
        'Authorization' => 'InvalidFormat token-12345',
    ]);

    $response->assertUnauthorized();
    $response->assertJson(['message' => 'Unauthenticated.']);
});

test('twitch stats endpoint rejects expired token', function () {
    // Create a user and generate a token
    $user = User::factory()->create([
        'name' => 'Test API User',
        'email' => 'api@test.local',
        'password' => Hash::make('password'),
    ]);

    $token = $user->createToken('test-token', ['*']);

    // Manually expire the token by updating its expires_at
    $personalAccessToken = PersonalAccessToken::findToken($token->plainTextToken);
    $personalAccessToken->expires_at = now()->subHour();
    $personalAccessToken->save();

    $response = $this->postJson('/api/twitch/stats', [
        'stats' => [
            [
                'userId' => '123456789',
                'userName' => 'testuser',
                'platform' => 'twitch',
                'name' => 'points',
                'value' => 100,
                'lastWrite' => now()->toISOString(),
            ],
        ],
    ], [
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ]);

    $response->assertUnauthorized();
    $response->assertJson(['message' => 'Token expired.']);
});

test('twitch stats endpoint works with api user token', function () {
    // Create the API user (same as in seeder)
    $apiUser = User::firstOrCreate(
        ['email' => 'api@streamerbot.local'],
        [
            'name' => 'StreamerBot API User',
            'password' => Hash::make('password'),
        ]
    );

    $token = $apiUser->createToken('streamerbot-poster', ['*']);

    $response = $this->postJson('/api/twitch/stats', [
        'stats' => [
            [
                'userId' => '123456789',
                'userName' => 'testuser',
                'platform' => 'twitch',
                'name' => 'points',
                'value' => 100,
                'lastWrite' => now()->toISOString(),
            ],
        ],
    ], [
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ]);

    $response->assertSuccessful();
    $response->assertJson([
        'status' => 'success',
        'message' => 'Twitch stats imported successfully',
    ]);
});
