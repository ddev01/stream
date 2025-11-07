<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('subscription history endpoint requires authentication', function () {
    $response = $this->postJson('/api/twitch/subscription-history', [
        'subscription_history' => [
            [
                '_id' => ['$oid' => '687abd7ac937df027d504867'],
                'subscribedAt' => ['$date' => '2025-07-18T21:32:42.0770000Z'],
                'userId' => '1271579073',
                'gifterUserId' => '214064796',
            ],
        ],
    ]);

    $response->assertUnauthorized();
    $response->assertJson(['message' => 'Unauthenticated.']);
});

test('subscription history endpoint accepts valid token', function () {
    $user = User::factory()->create([
        'name' => 'Test API User',
        'password' => Hash::make('password'),
    ]);

    $token = $user->createToken('test-token', ['*']);

    $response = $this->postJson('/api/twitch/subscription-history', [
        'subscription_history' => [
            [
                '_id' => ['$oid' => '687abd7ac937df027d504867'],
                'subscribedAt' => ['$date' => '2025-07-18T21:32:42.0770000Z'],
                'userId' => '1271579073',
                'gifterUserId' => '214064796',
            ],
        ],
    ], [
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ]);

    $response->assertStatus(202)
        ->assertJson([
            'status' => 'queued',
            'message' => 'Subscription history import queued for processing',
            'records' => 1,
        ]);
});

test('subscription history endpoint rejects invalid token', function () {
    $response = $this->postJson('/api/twitch/subscription-history', [
        'subscription_history' => [
            [
                '_id' => ['$oid' => '687abd7ac937df027d504867'],
                'subscribedAt' => ['$date' => '2025-07-18T21:32:42.0770000Z'],
                'userId' => '1271579073',
                'gifterUserId' => '214064796',
            ],
        ],
    ], [
        'Authorization' => 'Bearer invalid-token-12345',
    ]);

    $response->assertUnauthorized();
    $response->assertJson(['message' => 'Unauthenticated.']);
});

test('subscription history endpoint rejects malformed authorization header', function () {
    $response = $this->postJson('/api/twitch/subscription-history', [
        'subscription_history' => [
            [
                '_id' => ['$oid' => '687abd7ac937df027d504867'],
                'subscribedAt' => ['$date' => '2025-07-18T21:32:42.0770000Z'],
                'userId' => '1271579073',
                'gifterUserId' => '214064796',
            ],
        ],
    ], [
        'Authorization' => 'InvalidFormat token-12345',
    ]);

    $response->assertUnauthorized();
    $response->assertJson(['message' => 'Unauthenticated.']);
});

test('subscription history endpoint works with api user token', function () {
    $apiUser = User::firstOrCreate(
        ['name' => 'Development API User'],
        [
            'name' => 'StreamerBot API User',
            'password' => Hash::make('password'),
        ]
    );

    $token = $apiUser->createToken('streamerbot-poster', ['*']);

    $response = $this->postJson('/api/twitch/subscription-history', [
        'subscription_history' => [
            [
                '_id' => ['$oid' => '687abd7ac937df027d504867'],
                'subscribedAt' => ['$date' => '2025-07-18T21:32:42.0770000Z'],
                'userId' => '1271579073',
                'gifterUserId' => '214064796',
            ],
        ],
    ], [
        'Authorization' => 'Bearer '.$token->plainTextToken,
    ]);

    $response->assertStatus(202)
        ->assertJson([
            'status' => 'queued',
            'message' => 'Subscription history import queued for processing',
            'records' => 1,
        ]);
});

test('api endpoint allows requests authenticated with development API key', function () {
    config(['app.dev_api_key' => 'dev_helloworld12345']);

    $response = $this->postJson('/api/twitch/subscription-history', [
        'subscription_history' => [
            [
                '_id' => ['$oid' => '687abd7ac937df027d504867'],
                'subscribedAt' => ['$date' => '2025-07-18T21:32:42.0770000Z'],
                'userId' => '1271579073',
                'gifterUserId' => '214064796',
            ],
        ],
    ], [
        'Authorization' => 'Bearer dev_helloworld12345',
    ]);

    $response->assertStatus(202);
});
