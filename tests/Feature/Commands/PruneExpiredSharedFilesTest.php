<?php

declare(strict_types=1);

use App\Models\SharedFile;
use App\Models\TwitchUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('prune command deletes expired files and database records', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    TwitchUser::factory()->create([
        'user_id' => $user->id,
        'twitch_id' => '123',
    ]);

    // Create expired file
    $expiredPath = 'shared-files/expired.mp4';
    Storage::disk('local')->put($expiredPath, 'expired content');

    $expiredFile = SharedFile::create([
        'user_id' => $user->id,
        'original_filename' => 'expired.mp4',
        'stored_filename' => 'expired.mp4',
        'share_token' => 'expiredtoken',
        'mime_type' => 'video/mp4',
        'file_size' => 100,
        'file_path' => $expiredPath,
        'expires_at' => now()->subDay(),
    ]);

    // Create non-expired file
    $activePath = 'shared-files/active.mp4';
    Storage::disk('local')->put($activePath, 'active content');

    $activeFile = SharedFile::create([
        'user_id' => $user->id,
        'original_filename' => 'active.mp4',
        'stored_filename' => 'active.mp4',
        'share_token' => 'activetoken',
        'mime_type' => 'video/mp4',
        'file_size' => 100,
        'file_path' => $activePath,
        'expires_at' => now()->addDays(3),
    ]);

    // Create permanent file
    $permanentPath = 'shared-files/permanent.mp4';
    Storage::disk('local')->put($permanentPath, 'permanent content');

    $permanentFile = SharedFile::create([
        'user_id' => $user->id,
        'original_filename' => 'permanent.mp4',
        'stored_filename' => 'permanent.mp4',
        'share_token' => 'permanenttoken',
        'mime_type' => 'video/mp4',
        'file_size' => 100,
        'file_path' => $permanentPath,
        'expires_at' => null,
    ]);

    $this->artisan('shared-files:prune')
        ->assertSuccessful();

    // Expired file should be deleted
    expect(SharedFile::find($expiredFile->id))->toBeNull();
    Storage::disk('local')->assertMissing($expiredPath);

    // Active file should remain
    expect(SharedFile::find($activeFile->id))->not->toBeNull();
    Storage::disk('local')->assertExists($activePath);

    // Permanent file should remain
    expect(SharedFile::find($permanentFile->id))->not->toBeNull();
    Storage::disk('local')->assertExists($permanentPath);
});

test('prune command handles missing files gracefully', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    TwitchUser::factory()->create([
        'user_id' => $user->id,
        'twitch_id' => '123',
    ]);

    // Create expired file record but don't create the physical file
    $expiredFile = SharedFile::create([
        'user_id' => $user->id,
        'original_filename' => 'missing.mp4',
        'stored_filename' => 'missing.mp4',
        'share_token' => 'missingtoken',
        'mime_type' => 'video/mp4',
        'file_size' => 100,
        'file_path' => 'shared-files/missing.mp4',
        'expires_at' => now()->subDay(),
    ]);

    // Should not throw an error
    $this->artisan('shared-files:prune')
        ->assertSuccessful();

    // Database record should still be deleted
    expect(SharedFile::find($expiredFile->id))->toBeNull();
});

