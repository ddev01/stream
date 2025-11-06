<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

test('generate dev api key command creates new key', function () {
    $envPath = base_path('.env');
    $originalContent = File::exists($envPath) ? File::get($envPath) : null;

    // Remove DEV_API_KEY if it exists
    if ($originalContent && strpos($originalContent, 'DEV_API_KEY=') !== false) {
        $originalContent = preg_replace('/^DEV_API_KEY=.*$/m', '', $originalContent);
        File::put($envPath, $originalContent);
    }

    Artisan::call('dev:api-key');

    $output = Artisan::output();
    expect($output)->toContain('Development API key generated successfully!')
        ->and($output)->toContain('DEV_API_KEY=dev_');

    // Clean up
    if ($originalContent) {
        File::put($envPath, $originalContent);
    }
});

test('show dev api key command displays existing key', function () {
    $envPath = base_path('.env');
    $originalContent = File::exists($envPath) ? File::get($envPath) : null;

    // Ensure DEV_API_KEY exists
    if (! $originalContent || strpos($originalContent, 'DEV_API_KEY=') === false) {
        File::append($envPath, "\nDEV_API_KEY=dev_test123\n");
    }

    Artisan::call('dev:api-key', ['--show' => true]);

    $output = Artisan::output();
    expect($output)->toContain('DEV_API_KEY');

    // Clean up
    if ($originalContent) {
        File::put($envPath, $originalContent);
    }
});

test('show dev api key command warns when no key exists', function () {
    $envPath = base_path('.env');
    $originalContent = File::exists($envPath) ? File::get($envPath) : null;

    // Remove DEV_API_KEY if it exists
    if ($originalContent && strpos($originalContent, 'DEV_API_KEY=') !== false) {
        $content = preg_replace('/^DEV_API_KEY=.*$/m', '', $originalContent);
        File::put($envPath, $content);
    }

    Artisan::call('dev:api-key', ['--show' => true]);

    $output = Artisan::output();
    expect($output)->toContain('No DEV_API_KEY found');

    // Clean up
    if ($originalContent) {
        File::put($envPath, $originalContent);
    }
});
