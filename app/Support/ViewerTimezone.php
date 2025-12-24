<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonTimeZone;
use Illuminate\Http\Request;

/**
 * Resolve the viewer's timezone from the request in a safe way.
 */
final class ViewerTimezone
{
    /**
     * Resolve the viewer's timezone from the `timezone` cookie.
     */
    public static function resolve(Request $request): string
    {
        $default = config('app.timezone', 'UTC');

        $timezoneCookie = $request->cookie('timezone');

        if ($timezoneCookie === null) {
            return $default;
        }

        $timezoneCookie = \rawurldecode((string) $timezoneCookie);

        if (blank($timezoneCookie)) {
            return $default;
        }

        // Validate against known timezones; if invalid, fall back to default.
        return CarbonTimeZone::create($timezoneCookie) === false ? $default : $timezoneCookie;
    }
}
