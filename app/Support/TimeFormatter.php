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

    /**
     * Format a datetime into human-readable "time ago" format
     * Examples: "51m ago", "3h 45m ago", "2d ago"
     */
    public static function formatTimeAgo(\DateTimeInterface $datetime): string
    {
        $now = now();
        $diff = $now->diff($datetime);

        // If the datetime is in the future, return "just now"
        if ($datetime > $now) {
            return 'just now';
        }

        // If more than 1 day, just show days
        if ($diff->days > 0) {
            return $diff->days.'d ago';
        }

        // If we have hours
        if ($diff->h > 0) {
            $result = $diff->h.'h';
            // Add minutes if we have them
            if ($diff->i > 0) {
                $result .= ' '.$diff->i.'m';
            }

            return $result.' ago';
        }

        // Just minutes (or less than a minute, show as 0m)
        $minutes = max(0, $diff->i);

        return $minutes.'m ago';
    }
}
