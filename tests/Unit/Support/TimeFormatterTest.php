<?php

declare(strict_types=1);

use App\Support\TimeFormatter;

test('formatWatchtime formats seconds correctly', function () {
    expect(TimeFormatter::formatWatchtime(0))->toBe('0h 0m')
        ->and(TimeFormatter::formatWatchtime(60))->toBe('0h 1m')
        ->and(TimeFormatter::formatWatchtime(3600))->toBe('1h 0m')
        ->and(TimeFormatter::formatWatchtime(3660))->toBe('1h 1m')
        ->and(TimeFormatter::formatWatchtime(86400))->toBe('1d 0h 0m')
        ->and(TimeFormatter::formatWatchtime(90000))->toBe('1d 1h 0m')
        ->and(TimeFormatter::formatWatchtime(90060))->toBe('1d 1h 1m')
        ->and(TimeFormatter::formatWatchtime(172800))->toBe('2d 0h 0m');
});

test('formatWatchtime handles large values', function () {
    expect(TimeFormatter::formatWatchtime(259200))->toBe('3d 0h 0m') // 3 days
        ->and(TimeFormatter::formatWatchtime(259260))->toBe('3d 0h 1m') // 3 days + 1 minute
        ->and(TimeFormatter::formatWatchtime(259560))->toBe('3d 0h 6m') // 3 days + 6 minutes (360 seconds)
        ->and(TimeFormatter::formatWatchtime(262800))->toBe('3d 1h 0m'); // 3 days + 1 hour
});
