<?php

declare(strict_types=1);

use App\Models\SharedFile;
use App\Models\TwitchUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('shared file endpoint supports byte ranges for video streaming', function () {
    Storage::fake('local');

    // Ensure the share page can be accessed without auth, but uploads are admin-only.
    $user = User::factory()->create();
    TwitchUser::factory()->create([
        'user_id' => $user->id,
        'twitch_id' => '123',
    ]);

    $token = 'testtoken123';
    $path = 'shared-files/test.mp4';
    $contents = str_repeat('a', 1024);

    Storage::disk('local')->put($path, $contents);

    $sharedFile = SharedFile::create([
        'user_id' => $user->id,
        'original_filename' => 'test.mp4',
        'stored_filename' => 'test.mp4',
        'share_token' => $token,
        'mime_type' => 'video/mp4',
        'file_size' => 1024,
        'file_path' => $path,
        'expires_at' => now()->addDays(3),
    ]);

    $response = $this->get(route('shared-files.file', $sharedFile), [
        'Range' => 'bytes=0-99',
    ]);

    $response->assertStatus(206);
    $response->assertHeader('Accept-Ranges', 'bytes');
    $response->assertHeader('Content-Range', 'bytes 0-99/1024');

    $content = $response->streamedContent();
    expect(strlen($content))->toBe(100);
});

test('expired shared file returns 410 Gone on show', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    TwitchUser::factory()->create([
        'user_id' => $user->id,
        'twitch_id' => '123',
    ]);

    $token = 'expiredtoken';
    $path = 'shared-files/expired.mp4';
    $contents = str_repeat('a', 1024);

    Storage::disk('local')->put($path, $contents);

    $sharedFile = SharedFile::create([
        'user_id' => $user->id,
        'original_filename' => 'expired.mp4',
        'stored_filename' => 'expired.mp4',
        'share_token' => $token,
        'mime_type' => 'video/mp4',
        'file_size' => 1024,
        'file_path' => $path,
        'expires_at' => now()->subDay(),
    ]);

    $response = $this->get(route('shared-files.show', $sharedFile));

    $response->assertStatus(410);
});

test('expired shared file returns 410 Gone on file endpoint', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    TwitchUser::factory()->create([
        'user_id' => $user->id,
        'twitch_id' => '123',
    ]);

    $token = 'expiredtoken2';
    $path = 'shared-files/expired2.mp4';
    $contents = str_repeat('a', 1024);

    Storage::disk('local')->put($path, $contents);

    $sharedFile = SharedFile::create([
        'user_id' => $user->id,
        'original_filename' => 'expired2.mp4',
        'stored_filename' => 'expired2.mp4',
        'share_token' => $token,
        'mime_type' => 'video/mp4',
        'file_size' => 1024,
        'file_path' => $path,
        'expires_at' => now()->subDay(),
    ]);

    $response = $this->get(route('shared-files.file', $sharedFile));

    $response->assertStatus(410);
});

test('expired shared file returns 410 Gone on download endpoint', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    TwitchUser::factory()->create([
        'user_id' => $user->id,
        'twitch_id' => '123',
    ]);

    $token = 'expiredtoken3';
    $path = 'shared-files/expired3.mp4';
    $contents = str_repeat('a', 1024);

    Storage::disk('local')->put($path, $contents);

    $sharedFile = SharedFile::create([
        'user_id' => $user->id,
        'original_filename' => 'expired3.mp4',
        'stored_filename' => 'expired3.mp4',
        'share_token' => $token,
        'mime_type' => 'video/mp4',
        'file_size' => 1024,
        'file_path' => $path,
        'expires_at' => now()->subDay(),
    ]);

    $response = $this->get(route('shared-files.download', $sharedFile));

    $response->assertStatus(410);
});

test('permanent shared file does not expire', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    TwitchUser::factory()->create([
        'user_id' => $user->id,
        'twitch_id' => '123',
    ]);

    $token = 'permanenttoken';
    $path = 'shared-files/permanent.mp4';
    $contents = str_repeat('a', 1024);

    Storage::disk('local')->put($path, $contents);

    $sharedFile = SharedFile::create([
        'user_id' => $user->id,
        'original_filename' => 'permanent.mp4',
        'stored_filename' => 'permanent.mp4',
        'share_token' => $token,
        'mime_type' => 'video/mp4',
        'file_size' => 1024,
        'file_path' => $path,
        'expires_at' => null,
    ]);

    $response = $this->get(route('shared-files.show', $sharedFile));

    $response->assertSuccessful();
});
