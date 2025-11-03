<?php

$providers = [
    App\Providers\AppServiceProvider::class,
    App\Providers\VoltServiceProvider::class,
    \SocialiteProviders\Manager\ServiceProvider::class,
];

// Only register Telescope if it's installed (dev dependency)
if (class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)) {
    $providers[] = App\Providers\TelescopeServiceProvider::class;
}

return $providers;