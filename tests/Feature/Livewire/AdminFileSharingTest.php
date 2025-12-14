<?php

declare(strict_types=1);

use App\Livewire\Admin\FileSharing;
use App\Models\SharedFile;
use App\Models\TwitchUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('admin can upload a shared file', function () {
    Storage::fake('local');

    config(['admin.twitch_ids' => ['123']]);

    $user = User::factory()->create();
    TwitchUser::factory()->create([
        'user_id' => $user->id,
        'twitch_id' => '123',
    ]);

    Livewire::actingAs($user)
        ->test(FileSharing::class)
        ->set('file', UploadedFile::fake()->create('video.mp4', 10, 'video/mp4'))
        ->assertSet('shareUrl', fn ($url) => is_string($url) && $url !== '');

    $sharedFile = SharedFile::query()->first();

    expect($sharedFile)->not->toBeNull();
    expect($sharedFile->original_filename)->toBe('video.mp4');
    expect($sharedFile->share_token)->not->toBeEmpty();

    Storage::disk('local')->assertExists($sharedFile->file_path);
});

test('non-admin cannot access file sharing component', function () {
    config(['admin.twitch_ids' => ['123']]);

    $user = User::factory()->create();
    TwitchUser::factory()->create([
        'user_id' => $user->id,
        'twitch_id' => '999',
    ]);

    Livewire::actingAs($user)
        ->test(FileSharing::class)
        ->assertStatus(403);
});
