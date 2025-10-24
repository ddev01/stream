<?php

declare(strict_types=1);

use App\Models\TwitchUser;
use App\Models\TwitchUserStat;
use App\Services\TwitchStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

test('imports stats successfully', function () {
    $service = app(TwitchStatsService::class);

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

    $result = $service->importStats($stats);

    expect($result['status'])->toBe('success')
        ->and($result['imported'])->toBe(1)
        ->and($result['updated'])->toBe(0)
        ->and($result['total'])->toBe(1);

    expect(TwitchUser::count())->toBe(1);
    expect(TwitchUserStat::count())->toBe(1);
});

test('updates existing stats', function () {
    $service = app(TwitchStatsService::class);

    // Create initial stat
    $twitchUser = TwitchUser::factory()->create(['twitch_id' => '123']);
    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser->id,
        'name' => 'points',
        'value' => 50,
    ]);

    $stats = [
        [
            'userId' => '123',
            'userName' => 'testuser',
            'platform' => 'twitch',
            'name' => 'points',
            'value' => 150,
            'lastWrite' => now()->toISOString(),
        ],
    ];

    $result = $service->importStats($stats);

    expect($result['status'])->toBe('success')
        ->and($result['imported'])->toBe(0)
        ->and($result['updated'])->toBe(1)
        ->and($result['total'])->toBe(1);

    expect(TwitchUser::count())->toBe(1);
    expect(TwitchUserStat::count())->toBe(1);

    $stat = TwitchUserStat::first();
    expect($stat->value)->toBe('150');
});

test('handles empty stats array', function () {
    $service = app(TwitchStatsService::class);

    $result = $service->importStats([]);

    expect($result['status'])->toBe('success')
        ->and($result['imported'])->toBe(0)
        ->and($result['updated'])->toBe(0)
        ->and($result['total'])->toBe(0);
});

test('handles json values', function () {
    $service = app(TwitchStatsService::class);

    $stats = [
        [
            'userId' => '123',
            'userName' => 'testuser',
            'platform' => 'twitch',
            'name' => 'complexData',
            'value' => ['nested' => 'data', 'count' => 42],
            'lastWrite' => now()->toISOString(),
        ],
    ];

    $result = $service->importStats($stats);

    expect($result['status'])->toBe('success');

    $stat = TwitchUserStat::first();
    $decoded = json_decode($stat->value, true);
    expect($decoded)->toBeArray()
        ->and($decoded['nested'])->toBe('data')
        ->and($decoded['count'])->toBe(42);
});

test('batch imports multiple stats efficiently', function () {
    $service = app(TwitchStatsService::class);

    $stats = [];
    for ($i = 1; $i <= 100; $i++) {
        $stats[] = [
            'userId' => (string) $i,
            'userName' => "user{$i}",
            'platform' => 'twitch',
            'name' => 'points',
            'value' => $i * 10,
            'lastWrite' => now()->toISOString(),
        ];
    }

    $result = $service->importStats($stats);

    expect($result['status'])->toBe('success')
        ->and($result['imported'])->toBe(100)
        ->and($result['updated'])->toBe(0)
        ->and($result['total'])->toBe(100);

    expect(TwitchUser::count())->toBe(100);
    expect(TwitchUserStat::count())->toBe(100);
});

test('handles mixed import and update', function () {
    $service = app(TwitchStatsService::class);

    // Create one existing user with stat
    $twitchUser = TwitchUser::factory()->create(['twitch_id' => '1']);
    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser->id,
        'name' => 'points',
        'value' => 50,
    ]);

    $stats = [
        // Update existing
        [
            'userId' => '1',
            'userName' => 'user1',
            'platform' => 'twitch',
            'name' => 'points',
            'value' => 100,
            'lastWrite' => now()->toISOString(),
        ],
        // Create new
        [
            'userId' => '2',
            'userName' => 'user2',
            'platform' => 'twitch',
            'name' => 'points',
            'value' => 200,
            'lastWrite' => now()->toISOString(),
        ],
    ];

    $result = $service->importStats($stats);

    expect($result['status'])->toBe('success')
        ->and($result['imported'])->toBe(1)
        ->and($result['updated'])->toBe(1)
        ->and($result['total'])->toBe(2);

    expect(TwitchUser::count())->toBe(2);
    expect(TwitchUserStat::count())->toBe(2);
});

test('updates display name when provided', function () {
    $service = app(TwitchStatsService::class);

    $twitchUser = TwitchUser::factory()->create([
        'twitch_id' => '123',
        'display_name' => 'oldname',
    ]);

    $stats = [
        [
            'userId' => '123',
            'userName' => 'newname',
            'platform' => 'twitch',
            'name' => 'points',
            'value' => 100,
            'lastWrite' => now()->toISOString(),
        ],
    ];

    $service->importStats($stats);

    $twitchUser->refresh();
    expect($twitchUser->display_name)->toBe('newname');
});
