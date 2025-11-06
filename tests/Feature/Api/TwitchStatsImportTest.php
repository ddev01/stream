<?php

declare(strict_types=1);

use App\Models\TwitchUser;
use App\Models\TwitchUserStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// Helper function to create authenticated user and token
function createAuthenticatedUser(): array
{
    $user = User::factory()->create([
        'name' => 'Test API User',
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
            'userId' => '002345711',
            'platform' => 'twitch',
            'name' => 'points',
            'value' => 3000,
            'lastWrite' => '2025-10-21T18:30:44.8350000Z',
        ],
        [
            'userId' => '002345711',
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
        'Authorization' => 'Bearer '.$auth['token'],
    ]);

    $response->assertStatus(202)
        ->assertJson([
            'status' => 'queued',
            'message' => 'Twitch stats import queued for processing',
            'records' => 3,
        ]);

    // Process the queued job
    Artisan::call('queue:work', ['--once' => true, '--queue' => 'default']);

    expect(TwitchUser::count())->toBe(2)
        ->and(TwitchUserStat::count())->toBe(3);

    $twitchUser = TwitchUser::where('twitch_id', '002345711')->first();
    expect($twitchUser)->not->toBeNull();
    expect($twitchUser->stats()->count())->toBe(2);

    $pointsStat = $twitchUser->stats()->where('name', 'points')->first();
    expect($pointsStat->value)->toBe(3000);
});

test('can update existing stats', function () {
    $auth = createAuthenticatedUser();

    $twitchUser = TwitchUser::factory()->create(['twitch_id' => '002345711']);
    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser->id,
        'name' => 'points',
        'value' => 1000,
    ]);

    $stats = [
        [
            'userId' => '002345711',
            'platform' => 'twitch',
            'name' => 'points',
            'value' => 5000,
            'lastWrite' => '2025-10-23T12:00:00.0000000Z',
        ],
    ];

    $response = $this->postJson('/api/twitch/stats', ['stats' => $stats], [
        'Authorization' => 'Bearer '.$auth['token'],
    ]);

    $response->assertStatus(202)
        ->assertJson([
            'status' => 'queued',
            'message' => 'Twitch stats import queued for processing',
            'records' => 1,
        ]);

    // Process the queued job
    Artisan::call('queue:work', ['--once' => true, '--queue' => 'default']);

    $pointsStat = $twitchUser->stats()->where('name', 'points')->first();
    expect($pointsStat->value)->toBe(5000);
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
        'Authorization' => 'Bearer '.$auth['token'],
    ]);

    $response->assertStatus(202);

    // Process the queued job
    Artisan::call('queue:work', ['--once' => true, '--queue' => 'default']);

    expect(TwitchUser::count())->toBe(1);
    $twitchUser = TwitchUser::where('twitch_id', '999999999')->first();
    expect($twitchUser)->not->toBeNull()
        ->and($twitchUser->stats()->count())->toBe(1);
});

test('validates required fields for stats import', function () {
    $auth = createAuthenticatedUser();

    $stats = [
        [
            'userId' => '002345711',
            // Missing 'name' and 'value'
        ],
    ];

    $response = $this->postJson('/api/twitch/stats', ['stats' => $stats], [
        'Authorization' => 'Bearer '.$auth['token'],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['stats.0.name', 'stats.0.value']);
});

test('handles non-numeric values by skipping them', function () {
    $auth = createAuthenticatedUser();

    $stats = [
        [
            'userId' => '002345711',
            'platform' => 'twitch',
            'name' => 'complexData',
            'value' => ['nested' => 'data', 'count' => 42],
            'lastWrite' => '2025-10-23T12:00:00.0000000Z',
        ],
    ];

    $response = $this->postJson('/api/twitch/stats', ['stats' => $stats], [
        'Authorization' => 'Bearer '.$auth['token'],
    ]);

    $response->assertStatus(202);

    // Process the queued job
    Artisan::call('queue:work', ['--once' => true, '--queue' => 'default']);

    $twitchUser = TwitchUser::where('twitch_id', '002345711')->first();
    // Non-numeric values should be skipped, so no stat should be created
    $stat = $twitchUser->stats()->where('name', 'complexData')->first();
    expect($stat)->toBeNull();
});

test('requires stats array', function () {
    $auth = createAuthenticatedUser();

    $response = $this->postJson('/api/twitch/stats', [], [
        'Authorization' => 'Bearer '.$auth['token'],
    ]);

    $response->assertStatus(422);
});

test('userName field is populated on import', function () {
    $auth = createAuthenticatedUser();
    $stats = [[
        'userId' => '123',
        'userName' => 'foobar',
        'platform' => 'twitch',
        'name' => 'watchtime',
        'value' => 100,
        'lastWrite' => now()->toISOString(),
    ]];
    $response = $this->postJson('/api/twitch/stats', ['stats' => $stats], [
        'Authorization' => 'Bearer '.$auth['token'],
    ]);
    $response->assertStatus(202);

    // Process the queued job
    Artisan::call('queue:work', ['--once' => true, '--queue' => 'default']);

    $this->assertDatabaseHas('twitch_users', [
        'twitch_id' => '123',
        'display_name' => 'foobar',
    ]);
});

test('empty stats array is handled gracefully', function () {
    $auth = createAuthenticatedUser();
    $stats = [];
    $response = $this->postJson('/api/twitch/stats', ['stats' => $stats], [
        'Authorization' => 'Bearer '.$auth['token'],
    ]);
    $response->assertStatus(202)
        ->assertJson([
            'status' => 'queued',
            'message' => 'Twitch stats import queued for processing',
            'records' => 0,
        ]);

    // Process the queued job
    Artisan::call('queue:work', ['--once' => true, '--queue' => 'default']);
});

test('malformed data returns validation error', function () {
    $auth = createAuthenticatedUser();
    $stats = [
        ['foo' => 'bar'], // nonconformant input
    ];
    $response = $this->postJson('/api/twitch/stats', ['stats' => $stats], [
        'Authorization' => 'Bearer '.$auth['token'],
    ]);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['stats.0.userId', 'stats.0.name', 'stats.0.value']);
});
