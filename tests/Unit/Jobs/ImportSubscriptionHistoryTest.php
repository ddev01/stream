<?php

declare(strict_types=1);

use App\Jobs\ImportSubscriptionHistory;
use App\Models\SubscriptionHistory;
use App\Models\TwitchUser;
use App\Services\SubscriptionHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function () {
    Log::spy();
});

test('job handles successful import', function () {
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

    $job = new ImportSubscriptionHistory($subscriptionHistory);
    $job->handle(app(SubscriptionHistoryService::class));

    expect(SubscriptionHistory::where('oid', '687abd7ac937df027d504867')->exists())->toBeTrue();

    Log::shouldHaveReceived('info')->atLeast()->once()->with('Starting subscription history import job', \Mockery::type('array'));
    Log::shouldHaveReceived('info')->atLeast()->once()->with('Subscription history import job completed', \Mockery::type('array'));
});

test('job logs import details', function () {
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

    $job = new ImportSubscriptionHistory($subscriptionHistory);
    $job->handle(app(SubscriptionHistoryService::class));

    Log::shouldHaveReceived('info')->atLeast()->once()->with('Starting subscription history import job', \Mockery::on(function ($context) {
        return $context['records'] === 1;
    }));

    Log::shouldHaveReceived('info')->atLeast()->once()->with('Subscription history import job completed', \Mockery::on(function ($context) {
        return isset($context['imported']) && isset($context['updated']);
    }));
});

test('job handles exceptions and logs error', function () {
    $subscriptionHistory = [
        [
            '_id' => ['$oid' => '687abd7ac937df027d504867'],
            'subscribedAt' => ['$date' => 'invalid'],
            'userId' => '1271579073',
            'gifterUserId' => '214064796',
        ],
    ];

    $job = new ImportSubscriptionHistory($subscriptionHistory);

    // Mock the service to throw an exception
    $mockService = \Mockery::mock(SubscriptionHistoryService::class);
    $mockService->shouldReceive('importSubscriptionHistory')
        ->andThrow(new \Exception('Test exception'));

    try {
        $job->handle($mockService);
    } catch (\Exception $e) {
        // Expected to throw
    }

    Log::shouldHaveReceived('error')->once()->with('Subscription history import job failed', \Mockery::type('array'));
});

test('job failed method logs permanent failure', function () {
    $subscriptionHistory = [
        [
            '_id' => ['$oid' => '687abd7ac937df027d504867'],
            'subscribedAt' => ['$date' => '2025-07-18T21:32:42.0770000Z'],
            'userId' => '1271579073',
            'gifterUserId' => '214064796',
        ],
    ];

    $job = new ImportSubscriptionHistory($subscriptionHistory);
    $exception = new \Exception('Permanent failure');

    $job->failed($exception);

    Log::shouldHaveReceived('error')->once()->with('Subscription history import job permanently failed', \Mockery::on(function ($context) {
        return isset($context['records']) && isset($context['error']);
    }));
});

test('job has correct configuration', function () {
    $job = new ImportSubscriptionHistory([]);

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBe(30)
        ->and($job->timeout)->toBe(120);
});
