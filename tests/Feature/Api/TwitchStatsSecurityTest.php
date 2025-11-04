<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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

    $response->assertStatus(202)
        ->assertJson([
            'status' => 'queued',
            'message' => 'Twitch stats import queued for processing',
            'records' => 1,
        ]);

    // Process the queued job
    Artisan::call('queue:work', ['--once' => true, '--queue' => 'default']);
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
        ['name' => 'Development API User'],
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

    $response->assertStatus(202)
        ->assertJson([
            'status' => 'queued',
            'message' => 'Twitch stats import queued for processing',
            'records' => 1,
        ]);

    // Process the queued job
    Artisan::call('queue:work', ['--once' => true, '--queue' => 'default']);
});

test('api endpoint allows requests authenticated with development API key', function () {
    // Emulate dev key from .env
    config(['app.dev_api_key' => 'dev_helloworld12345']);
    $stats = [[
        'userId' => '789', 'userName' => 'zzz', 'platform' => 'twitch', 'name' => 'points', 'value' => 1,
    ]];
    $response = $this->postJson('/api/twitch/stats', ['stats' => $stats], [
        'Authorization' => 'Bearer dev_helloworld12345',
    ]);
    $response->assertStatus(202);

    // Process the queued job
    Artisan::call('queue:work', ['--once' => true, '--queue' => 'default']);

    $this->assertDatabaseHas('twitch_users', ['twitch_id' => '789', 'display_name' => 'zzz']);
});

test('api endpoint accepts both Sanctum and dev API keys', function () {
    config(['app.dev_api_key' => 'dev_helloworldabcde']);
    $user = \App\Models\User::factory()->create();
    $sanctum = $user->createToken('foo')->plainTextToken;
    $stats = [[
        'userId' => '900', 'userName' => 'a', 'platform' => 'twitch', 'name' => 'points', 'value' => 6,
    ]];
    $devResp = $this->postJson('/api/twitch/stats', ['stats' => $stats], [
        'Authorization' => 'Bearer dev_helloworldabcde',
    ]);
    $devResp->assertStatus(202);

    // Process the queued job
    Artisan::call('queue:work', ['--once' => true, '--queue' => 'default']);

    $sanctumResp = $this->postJson('/api/twitch/stats', ['stats' => $stats], [
        'Authorization' => 'Bearer '.$sanctum,
    ]);
    $sanctumResp->assertStatus(202);

    // Process the queued job
    Artisan::call('queue:work', ['--once' => true, '--queue' => 'default']);
});
