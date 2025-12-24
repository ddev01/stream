<?php

declare(strict_types=1);

namespace App\Providers;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

final class FilamentAssetsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register the Filament CSS file published by: php artisan filament:assets
        FilamentAsset::register([
            Css::make('app')
                ->relativePublicPath('css/filament/filament/app.css'),
        ], 'filament/filament');
    }
}

