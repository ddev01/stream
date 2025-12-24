<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Shared file expiration presets and calculations.
 */
final class SharedFileExpiry
{
    /**
     * Get the available expiration presets.
     *
     * @return array<string, string>
     */
    public static function presetOptions(): array
    {
        return [
            '30m' => '30 minutes',
            '1h' => '1 hour',
            '12h' => '12 hours',
            '1d' => '1 day',
            '3d' => '3 days',
            '7d' => '7 days',
            '30d' => '30 days',
            'permanent' => 'Permanent',
        ];
    }

    /**
     * Calculate an expires_at value for a preset.
     */
    public static function expiresAt(string $preset): ?Carbon
    {
        $preset = self::normalizePreset($preset);

        return match ($preset) {
            '30m' => now('UTC')->addMinutes(30),
            '1h' => now('UTC')->addHour(),
            '12h' => now('UTC')->addHours(12),
            '1d' => now('UTC')->addDay(),
            '3d' => now('UTC')->addDays(3),
            '7d' => now('UTC')->addDays(7),
            '30d' => now('UTC')->addDays(30),
            'permanent' => null,
            default => now('UTC')->addDays(3),
        };
    }

    /**
     * Normalize legacy preset values to the current preset keys.
     */
    public static function normalizePreset(string $preset): string
    {
        return match ($preset) {
            '30_minutes' => '30m',
            '1_hour' => '1h',
            '12_hours' => '12h',
            '1_day' => '1d',
            '3_days' => '3d',
            '7_days' => '7d',
            '30_days' => '30d',
            default => $preset,
        };
    }
}
