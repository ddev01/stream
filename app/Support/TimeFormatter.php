<?php

namespace App\Support;

/**
 * Helper class for formatting time values
 */
class TimeFormatter
{
    /**
     * Format seconds into human-readable watchtime format (days, hours, minutes)
     */
    public static function formatWatchtime(int $seconds): string
    {
        $result = '';

        // Days
        if ($seconds >= 86400) {
            $days = floor($seconds / 86400);
            $result .= $days.'d ';
            $seconds = $seconds % 86400;
        }

        // Hours
        $hours = floor($seconds / 3600);
        $result .= $hours.'h ';

        // Minutes
        $minutes = floor(($seconds % 3600) / 60);
        $result .= $minutes.'m';

        return trim($result);
    }
}
