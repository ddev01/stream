<?php

declare(strict_types=1);

use App\Models\SubscriptionHistory;
use App\Models\TwitchUser;
use App\Services\SubscriptionHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

test('imports subscription history successfully', function () {
    $user = TwitchUser::factory()->create(['twitch_id' => '1271579073']);
    $gifter = TwitchUser::factory()->create(['twitch_id' => '214064796']);

    $subscriptionHistory = [
        [
            '_id' => ['$oid' => '687abd7ac937df027d504867'],
            'subscribedAt' => ['$date' => '2025-07-18T21:32:42.0770000Z'],
            'userId' => '1271579073',
            'gifterUserId' => '214064796',
        ],
    ];

    $service = new SubscriptionHistoryService;
    $result = $service->importSubscriptionHistory($subscriptionHistory);

    expect($result['status'])->toBe('success')
        ->and($result['imported'])->toBe(1)
        ->and($result['updated'])->toBe(0)
        ->and(SubscriptionHistory::where('oid', '687abd7ac937df027d504867')->exists())->toBeTrue();
});

test('updates existing subscription history', function () {
    $user = TwitchUser::factory()->create(['twitch_id' => '1271579073']);
    $gifter = TwitchUser::factory()->create(['twitch_id' => '214064796']);

    SubscriptionHistory::create([
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

    $service = new SubscriptionHistoryService;
    $result = $service->importSubscriptionHistory($subscriptionHistory);

    expect($result['imported'])->toBe(0)
        ->and($result['updated'])->toBe(1);
});

test('filters out subscriptions with missing users', function () {
    $user = TwitchUser::factory()->create(['twitch_id' => '1271579073']);
    // Don't create the gifter user

    $subscriptionHistory = [
        [
            '_id' => ['$oid' => '687abd7ac937df027d504867'],
            'subscribedAt' => ['$date' => '2025-07-18T21:32:42.0770000Z'],
            'userId' => '1271579073',
            'gifterUserId' => '999999999', // Non-existent
        ],
    ];

    $service = new SubscriptionHistoryService;
    $result = $service->importSubscriptionHistory($subscriptionHistory);

    expect($result['valid'])->toBe(0)
        ->and(SubscriptionHistory::count())->toBe(0);
});

test('requires gifterUserId in subscription history', function () {
    $user = TwitchUser::factory()->create(['twitch_id' => '1271579073']);
    $gifter = TwitchUser::factory()->create(['twitch_id' => '214064796']);

    $subscriptionHistory = [
        [
            '_id' => ['$oid' => '687abd7ac937df027d504867'],
            'subscribedAt' => ['$date' => '2025-07-18T21:32:42.0770000Z'],
            'userId' => '1271579073',
            'gifterUserId' => '214064796',
        ],
    ];

    $service = new SubscriptionHistoryService;
    $result = $service->importSubscriptionHistory($subscriptionHistory);

    expect($result['imported'])->toBe(1)
        ->and(SubscriptionHistory::first()->gifter_user_id)->toBe('214064796');
});

test('handles empty subscription history array', function () {
    $service = new SubscriptionHistoryService;
    $result = $service->importSubscriptionHistory([]);

    expect($result['status'])->toBe('success')
        ->and($result['total'])->toBe(0)
        ->and($result['imported'])->toBe(0)
        ->and($result['updated'])->toBe(0);
});

test('skips invalid date formats', function () {
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

    $service = new SubscriptionHistoryService;
    $result = $service->importSubscriptionHistory($subscriptionHistory);

    expect($result['valid'])->toBe(0)
        ->and(SubscriptionHistory::count())->toBe(0);
});

test('skips records with missing oid', function () {
    $user = TwitchUser::factory()->create(['twitch_id' => '1271579073']);
    $gifter = TwitchUser::factory()->create(['twitch_id' => '214064796']);

    $subscriptionHistory = [
        [
            'subscribedAt' => ['$date' => '2025-07-18T21:32:42.0770000Z'],
            'userId' => '1271579073',
            'gifterUserId' => '214064796',
        ],
    ];

    $service = new SubscriptionHistoryService;
    $result = $service->importSubscriptionHistory($subscriptionHistory);

    expect($result['valid'])->toBe(0);
});

test('handles multiple subscriptions in batch', function () {
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

    $service = new SubscriptionHistoryService;
    $result = $service->importSubscriptionHistory($subscriptionHistory);

    expect($result['imported'])->toBe(2)
        ->and(SubscriptionHistory::count())->toBe(2);
});
