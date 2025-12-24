<?php

declare(strict_types=1);

use App\Livewire\Admin\MySharedFilesTable;
use App\Models\SharedFile;
use App\Models\TwitchUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('my shared files table renders and lists the authenticated admin user records', function () {
    config(['admin.twitch_ids' => ['123']]);

    $user = User::factory()->create();
    TwitchUser::factory()->create([
        'user_id' => $user->id,
        'twitch_id' => '123',
    ]);

    $sharedFile = SharedFile::create([
        'user_id' => $user->id,
        'original_filename' => 'test.txt',
        'stored_filename' => 'test.txt',
        'share_token' => 'testtoken',
        'mime_type' => 'text/plain',
        'file_size' => 123,
        'file_path' => 'shared-files/test.txt',
    ]);

    $sharedFile->forceFill([
        'created_at' => Carbon::parse('2025-12-24 01:00:00', 'UTC'),
    ])->save();

    Livewire::withCookie('timezone', 'Europe/Amsterdam')
        ->actingAs($user)
        ->test(MySharedFilesTable::class)
        ->assertSee('Filename')
        ->assertSee('test.txt')
        ->assertSee('02:00');
});

test('my uploads page requires authentication', function () {
    $this->get(route('admin.my-uploads'))
        ->assertRedirect('/login');
});

test('my uploads page is forbidden for non-admin users', function () {
    config(['admin.twitch_ids' => ['123']]);

    $user = User::factory()->create();
    TwitchUser::factory()->create([
        'user_id' => $user->id,
        'twitch_id' => '999',
    ]);

    $this->actingAs($user)
        ->get(route('admin.my-uploads'))
        ->assertForbidden();
});
