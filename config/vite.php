<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vite Asset Proxy (Local Development)
    |--------------------------------------------------------------------------
    |
    | When developing with Vite running in a separate container/host, the app may
    | need to proxy requests for dev assets. This is only used when the route is
    | registered in the local environment.
    |
    */

    'asset_proxy_url' => env('VITE_ASSET_PROXY_URL', 'http://localhost:5173'),

    /*
    |--------------------------------------------------------------------------
    | Vite Asset Proxy Timeout (Seconds)
    |--------------------------------------------------------------------------
    */

    'asset_proxy_timeout' => (int) env('VITE_ASSET_PROXY_TIMEOUT', 10),
];
