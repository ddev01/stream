<?php

declare(strict_types=1);

use App\Models\TwitchUser;
use App\Models\User;
use App\Services\TwitchAuthService;
use App\Services\TwitchStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

test('viewPulse gate allows access in local environment', function () {
    // The gate checks app()->environment('local') which uses config('app.env')
    // In tests, the environment is typically 'testing', so we need to mock it
    $originalEnv = config('app.env');

    // Use app()->detectEnvironment to override
    $this->app->detectEnvironment(function () {
        return 'local';
    });

    $allowed = Gate::allows('viewPulse');

    expect($allowed)->toBeTrue();

    // Restore
    $this->app->detectEnvironment(function () use ($originalEnv) {
        return $originalEnv;
    });
});

test('viewPulse gate denies access for unauthenticated users', function () {
    $originalEnv = config('app.env');
    $this->app->detectEnvironment(function () {
        return 'production';
    });

    $allowed = Gate::allows('viewPulse');

    expect($allowed)->toBeFalse();

    $this->app->detectEnvironment(function () use ($originalEnv) {
        return $originalEnv;
    });
});

test('viewPulse gate denies access for users without twitch user', function () {
    $originalEnv = config('app.env');
    $this->app->detectEnvironment(function () {
        return 'production';
    });

    $user = User::factory()->create();
    $this->actingAs($user);

    $allowed = Gate::allows('viewPulse');

    expect($allowed)->toBeFalse();

    $this->app->detectEnvironment(function () use ($originalEnv) {
        return $originalEnv;
    });
});

test('viewPulse gate allows access for admin users', function () {
    $originalEnv = config('app.env');
    $this->app->detectEnvironment(function () {
        return 'production';
    });
    config(['admin.twitch_ids' => ['12345']]);

    $user = User::factory()->create();
    $twitchUser = TwitchUser::factory()->create([
        'user_id' => $user->id,
        'twitch_id' => '12345',
    ]);

    $this->actingAs($user);

    $allowed = Gate::allows('viewPulse');

    expect($allowed)->toBeTrue();

    $this->app->detectEnvironment(function () use ($originalEnv) {
        return $originalEnv;
    });
});

test('viewPulse gate denies access for non-admin users', function () {
    $originalEnv = config('app.env');
    $this->app->detectEnvironment(function () {
        return 'production';
    });
    config(['admin.twitch_ids' => ['12345']]);

    $user = User::factory()->create();
    $twitchUser = TwitchUser::factory()->create([
        'user_id' => $user->id,
        'twitch_id' => '99999', // Not in admin list
    ]);

    $this->actingAs($user);

    $allowed = Gate::allows('viewPulse');

    expect($allowed)->toBeFalse();

    $this->app->detectEnvironment(function () use ($originalEnv) {
        return $originalEnv;
    });
});

test('services are registered as singletons', function () {
    $service1 = app(TwitchStatsService::class);
    $service2 = app(TwitchStatsService::class);

    expect($service1)->toBe($service2);

    $authService1 = app(TwitchAuthService::class);
    $authService2 = app(TwitchAuthService::class);

    expect($authService1)->toBe($authService2);
});
