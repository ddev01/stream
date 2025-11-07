<?php

declare(strict_types=1);

use App\Models\SubscriptionHistory;
use App\Models\TwitchUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('can bulk import subscription history', function () {
    $auth = createAuthenticatedUser();

    // Create required Twitch users first
    $user1 = TwitchUser::factory()->create(['twitch_id' => '1271579073']);
    $gifter = TwitchUser::factory()->create(['twitch_id' => '214064796']);

    $subscriptionHistory = [
        [
            '_id' => ['$oid' => '687abd7ac937df027d504867'],
            'subscribedAt' => ['$date' => '2025-07-18T21:32:42.0770000Z'],
            'userId' => '1271579073',
            'gifterUserId' => '214064796',
        ],
    ];

    $response = $this->postJson('/api/twitch/subscription-history', [
        'subscription_history' => $subscriptionHistory,
    ], [
        'Authorization' => 'Bearer '.$auth['token'],
    ]);

    $response->assertStatus(202)
        ->assertJson([
            'status' => 'queued',
            'message' => 'Subscription history import queued for processing',
            'records' => 1,
        ]);

    // Process the queued job
    Artisan::call('queue:work', ['--once' => true, '--queue' => 'default']);

    expect(SubscriptionHistory::count())->toBe(1);

    $subscription = SubscriptionHistory::first();
    expect($subscription->oid)->toBe('687abd7ac937df027d504867')
        ->and($subscription->user_id)->toBe('1271579073')
        ->and($subscription->gifter_user_id)->toBe('214064796')
        ->and($subscription->subscribed_at)->not->toBeNull();
});

test('can update existing subscription history', function () {
    $auth = createAuthenticatedUser();

    $user = TwitchUser::factory()->create(['twitch_id' => '1271579073']);
    $gifter = TwitchUser::factory()->create(['twitch_id' => '214064796']);

    $existing = SubscriptionHistory::create([
        'oid' => '687abd7ac937df027d504867',
        'user_id' => '1271579073',
        'gifter_user_id' => '214064796',
        'subscribed_at' => '2025-07-18T21:32:42',
    ]);

    $subscriptionHistory = [
        [
            '_id' => ['$oid' => '687abd7ac937df027d504867'],
            'subscribedAt' => ['$date' => '2025-07-19T10:00:00.0000000Z'],
            'userId' => '1271579073',
            'gifterUserId' => '214064796',
        ],
    ];

    $response = $this->postJson('/api/twitch/subscription-history', [
        'subscription_history' => $subscriptionHistory,
    ], [
        'Authorization' => 'Bearer '.$auth['token'],
    ]);

    $response->assertStatus(202);

    // Process the queued job
    Artisan::call('queue:work', ['--once' => true, '--queue' => 'default']);

    $existing->refresh();
    expect($existing->subscribed_at->format('Y-m-d'))->toBe('2025-07-19');
});

test('filters out subscriptions with missing users', function () {
    $auth = createAuthenticatedUser();

    // Only create one user, not the gifter
    $user = TwitchUser::factory()->create(['twitch_id' => '1271579073']);

    $subscriptionHistory = [
        [
            '_id' => ['$oid' => '687abd7ac937df027d504867'],
            'subscribedAt' => ['$date' => '2025-07-18T21:32:42.0770000Z'],
            'userId' => '1271579073',
            'gifterUserId' => '999999999', // Non-existent user
        ],
    ];

    $response = $this->postJson('/api/twitch/subscription-history', [
        'subscription_history' => $subscriptionHistory,
    ], [
        'Authorization' => 'Bearer '.$auth['token'],
    ]);

    $response->assertStatus(202);

    // Process the queued job
    Artisan::call('queue:work', ['--once' => true, '--queue' => 'default']);

    // Should not create subscription because gifter doesn't exist
    expect(SubscriptionHistory::count())->toBe(0);
});

test('requires gifterUserId field', function () {
    $auth = createAuthenticatedUser();

    $user = TwitchUser::factory()->create(['twitch_id' => '1271579073']);

    $subscriptionHistory = [
        [
            '_id' => ['$oid' => '687abd7ac937df027d504867'],
            'subscribedAt' => ['$date' => '2025-07-18T21:32:42.0770000Z'],
            'userId' => '1271579073',
            // Missing gifterUserId
        ],
    ];

    $response = $this->postJson('/api/twitch/subscription-history', [
        'subscription_history' => $subscriptionHistory,
    ], [
        'Authorization' => 'Bearer '.$auth['token'],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['subscription_history.0.gifterUserId']);
});

test('validates required fields for subscription history import', function () {
    $auth = createAuthenticatedUser();

    $subscriptionHistory = [
        [
            // Missing required fields
        ],
    ];

    $response = $this->postJson('/api/twitch/subscription-history', [
        'subscription_history' => $subscriptionHistory,
    ], [
        'Authorization' => 'Bearer '.$auth['token'],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'subscription_history.0._id',
            'subscription_history.0.subscribedAt',
            'subscription_history.0.userId',
            'subscription_history.0.gifterUserId',
        ]);
});

test('requires subscription_history array', function () {
    $auth = createAuthenticatedUser();

    $response = $this->postJson('/api/twitch/subscription-history', [], [
        'Authorization' => 'Bearer '.$auth['token'],
    ]);

    $response->assertStatus(422);
});

test('empty subscription_history array is handled gracefully', function () {
    $auth = createAuthenticatedUser();

    $response = $this->postJson('/api/twitch/subscription-history', [
        'subscription_history' => [],
    ], [
        'Authorization' => 'Bearer '.$auth['token'],
    ]);

    $response->assertStatus(202)
        ->assertJson([
            'status' => 'queued',
            'message' => 'Subscription history import queued for processing',
            'records' => 0,
        ]);

    // Process the queued job
    Artisan::call('queue:work', ['--once' => true, '--queue' => 'default']);
});

test('handles invalid date format', function () {
    $auth = createAuthenticatedUser();

    $user = TwitchUser::factory()->create(['twitch_id' => '1271579073']);
    $gifter = TwitchUser::factory()->create(['twitch_id' => '214064796']);

    $subscriptionHistory = [
        [
            '_id' => ['$oid' => '687abd7ac937df027d504867'],
            'subscribedAt' => ['$date' => 'invalid-date'],
            'userId' => '1271579073',
            'gifterUserId' => '214064796',
        ],
    ];

    $response = $this->postJson('/api/twitch/subscription-history', [
        'subscription_history' => $subscriptionHistory,
    ], [
        'Authorization' => 'Bearer '.$auth['token'],
    ]);

    $response->assertStatus(202);

    // Process the queued job
    Artisan::call('queue:work', ['--once' => true, '--queue' => 'default']);

    // Invalid date should be skipped
    expect(SubscriptionHistory::count())->toBe(0);
});

test('handles multiple subscriptions in one import', function () {
    $auth = createAuthenticatedUser();

    $user1 = TwitchUser::factory()->create(['twitch_id' => '1271579073']);
    $user2 = TwitchUser::factory()->create(['twitch_id' => '1322556019']);
    $gifter = TwitchUser::factory()->create(['twitch_id' => '214064796']);

    $subscriptionHistory = [
        [
            '_id' => ['$oid' => '687abd7ac937df027d504867'],
            'subscribedAt' => ['$date' => '2025-07-18T21:32:42.0770000Z'],
            'userId' => '1271579073',
            'gifterUserId' => '214064796',
        ],
        [
            '_id' => ['$oid' => '687abd7ac937df027d504868'],
            'subscribedAt' => ['$date' => '2025-07-18T21:32:42.1030000Z'],
            'userId' => '1322556019',
            'gifterUserId' => '214064796',
        ],
    ];

    $response = $this->postJson('/api/twitch/subscription-history', [
        'subscription_history' => $subscriptionHistory,
    ], [
        'Authorization' => 'Bearer '.$auth['token'],
    ]);

    $response->assertStatus(202)
        ->assertJson([
            'records' => 2,
        ]);

    // Process the queued job
    Artisan::call('queue:work', ['--once' => true, '--queue' => 'default']);

    expect(SubscriptionHistory::count())->toBe(2);
});
