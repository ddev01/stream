<?php

declare(strict_types=1);

use App\Models\SharedFile;
use Illuminate\Support\Carbon;

test('shared file short time remaining label is compact and not rounded down', function () {
    Carbon::setTestNow(Carbon::parse('2025-12-24 00:00:00', 'UTC'));

    try {
        $sharedFile = new SharedFile;

        $sharedFile->expires_at = Carbon::now('UTC')->addDays(3);
        expect($sharedFile->time_remaining_short_label)->toBe('3d');

        Carbon::setTestNow(Carbon::parse('2025-12-24 00:01:00', 'UTC'));
        expect($sharedFile->time_remaining_short_label)->toBe('2d 23h');

        $sharedFile->expires_at = Carbon::now('UTC')->addHours(5)->addMinutes(12);
        expect($sharedFile->time_remaining_short_label)->toBe('5h 12m');

        $sharedFile->expires_at = Carbon::now('UTC')->addMinutes(59);
        expect($sharedFile->time_remaining_short_label)->toBe('59m');

        $sharedFile->expires_at = Carbon::now('UTC')->subMinute();
        expect($sharedFile->time_remaining_short_label)->toBe('Expired');

        $sharedFile->expires_at = null;
        expect($sharedFile->time_remaining_short_label)->toBe('Permanent');
    } finally {
        Carbon::setTestNow();
    }
});
