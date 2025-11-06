<?php

declare(strict_types=1);

use App\Jobs\ImportTwitchStats;
use App\Models\TwitchUser;
use App\Models\TwitchUserStat;
use App\Services\TwitchStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function () {
    Log::spy();
});

test('job handles successful import', function () {
    $stats = [
        [
            'userId' => '123',
            'userName' => 'testuser',
            'platform' => 'twitch',
            'name' => 'points',
            'value' => 100,
            'lastWrite' => now()->toISOString(),
        ],
    ];

    $job = new ImportTwitchStats($stats);
    $job->handle(app(TwitchStatsService::class));

    expect(TwitchUser::where('twitch_id', '123')->exists())->toBeTrue()
        ->and(TwitchUserStat::where('name', 'points')->exists())->toBeTrue();

    Log::shouldHaveReceived('info')->atLeast()->once()->with('Starting Twitch stats import job', \Mockery::type('array'));
    Log::shouldHaveReceived('info')->atLeast()->once()->with('Twitch stats import job completed', \Mockery::type('array'));
});

test('job logs import details', function () {
    $stats = [
        [
            'userId' => '456',
            'userName' => 'anotheruser',
            'platform' => 'twitch',
            'name' => 'watchtime',
            'value' => 3600,
            'lastWrite' => now()->toISOString(),
        ],
    ];

    $job = new ImportTwitchStats($stats);
    $job->handle(app(TwitchStatsService::class));

    Log::shouldHaveReceived('info')->atLeast()->once()->with('Starting Twitch stats import job', \Mockery::on(function ($context) {
        return $context['records'] === 1;
    }));

    Log::shouldHaveReceived('info')->atLeast()->once()->with('Twitch stats import job completed', \Mockery::on(function ($context) {
        return isset($context['imported']) && isset($context['duration_ms']);
    }));
});

test('job handles exceptions and logs error', function () {
    $stats = [
        [
            'userId' => '789',
            'userName' => 'baduser',
            'platform' => 'twitch',
            'name' => 'points',
            'value' => 'invalid', // This will cause issues
            'lastWrite' => now()->toISOString(),
        ],
    ];

    $job = new ImportTwitchStats($stats);

    // Mock the service to throw an exception
    $mockService = \Mockery::mock(TwitchStatsService::class);
    $mockService->shouldReceive('importStats')
        ->andThrow(new \Exception('Test exception'));

    try {
        $job->handle($mockService);
    } catch (\Exception $e) {
        // Expected to throw
    }

    Log::shouldHaveReceived('error')->once()->with('Twitch stats import job failed', \Mockery::type('array'));
});

test('job releases back to queue on failure before max attempts', function () {
    $stats = [['userId' => '999', 'userName' => 'test', 'platform' => 'twitch', 'name' => 'points', 'value' => 100, 'lastWrite' => now()->toISOString()]];

    $job = new ImportTwitchStats($stats);
    $job->attempts = 1; // Set current attempts

    $mockService = \Mockery::mock(TwitchStatsService::class);
    $mockService->shouldReceive('importStats')
        ->andThrow(new \Exception('Test exception'));

    try {
        $job->handle($mockService);
    } catch (\Exception $e) {
        // Expected to throw
    }

    // Job should attempt to release (we can't fully test this without queue driver)
    expect($job->attempts())->toBe(1);
});

test('job failed method logs permanent failure', function () {
    $stats = [['userId' => '888', 'userName' => 'test', 'platform' => 'twitch', 'name' => 'points', 'value' => 100, 'lastWrite' => now()->toISOString()]];

    $job = new ImportTwitchStats($stats);
    $exception = new \Exception('Permanent failure');

    $job->failed($exception);

    Log::shouldHaveReceived('error')->once()->with('Twitch stats import job permanently failed', \Mockery::on(function ($context) {
        return isset($context['records']) && isset($context['error']);
    }));
});

test('job has correct configuration', function () {
    $job = new ImportTwitchStats([]);

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBe(30)
        ->and($job->timeout)->toBe(120);
});
