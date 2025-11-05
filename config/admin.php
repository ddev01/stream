<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Twitch IDs
    |--------------------------------------------------------------------------
    |
    | This array contains the Twitch user IDs that are authorized to access
    | admin-only features like Horizon, Telescope, and Pulse dashboards.
    | Multiple IDs can be specified by separating them with commas in the
    | ADMIN_TWITCH_IDS environment variable.
    |
    */

    'twitch_ids' => array_map(
        fn ($id) => trim((string) $id),
        array_filter(
            explode(',', env('ADMIN_TWITCH_IDS', ''))
        )
    ),
];
