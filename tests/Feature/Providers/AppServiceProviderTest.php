<?php

declare(strict_types=1);

use App\Services\TwitchAuthService;
use App\Services\TwitchStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('services are registered as singletons', function () {
    $service1 = app(TwitchStatsService::class);
    $service2 = app(TwitchStatsService::class);

    expect($service1)->toBe($service2);

    $authService1 = app(TwitchAuthService::class);
    $authService2 = app(TwitchAuthService::class);

    expect($authService1)->toBe($authService2);
});
