<?php

declare(strict_types=1);

use App\Models\TwitchUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Ensure clean state
    Log::shouldReceive('error')->andReturnNull();
});

test('redirect to twitch oauth provider', function () {
    Socialite::shouldReceive('driver->redirect')->andReturn(redirect('https://twitch.tv/oauth/authorize'));
    $this->get('/auth/twitch')->assertRedirect();
});

test('callback creates new user and twitch user', function () {
    $socialiteUser = mockSocialiteUser('1', 'alpha', 'alpha@example.com');
    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);
    $resp = $this->get('/auth/twitch/callback');
    $resp->assertRedirect('/dashboard');
    expect(User::count())->toBe(1)->and(TwitchUser::count())->toBe(1);
    $user = User::first();
    $tw = TwitchUser::first();
    expect($user->name)->toBe('alpha')
        ->and($tw->user_id)->toBe($user->id);
});

test('callback enriches existing twitch user', function () {
    $tw = TwitchUser::factory()->create(['twitch_id' => '2', 'profile_image_url' => null]);
    $socialiteUser = mockSocialiteUser('2', 'enrich', 'enrich@example.com');
    $socialiteUser->user['profile_image_url'] = 'http://x.com/p.png';
    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);
    $resp = $this->get('/auth/twitch/callback');
    $resp->assertRedirect('/dashboard');
    $tw->refresh();
    expect($tw->profile_image_url)->toBe('http://x.com/p.png');
});

test('callback links existing user through twitch user', function () {
    $user = User::factory()->create(['name' => 'Existing User']);
    $existingTwitchUser = TwitchUser::factory()->create([
        'twitch_id' => '99',
        'user_id' => $user->id,
        'display_name' => 'OldName',
    ]);
    $socialiteUser = mockSocialiteUser('99', 'zzz', 'test@example.com');
    Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);
    $this->get('/auth/twitch/callback')->assertRedirect('/dashboard');
    $tw = TwitchUser::where('twitch_id', '99')->first();
    expect($tw->user_id)->toBe($user->id)
        ->and($tw->display_name)->toBe('zzz');
});

test('callback handles socialite exception gracefully', function () {
    Socialite::shouldReceive('driver->user')->andThrow(new Exception('fail-err'));
    $resp = $this->get('/auth/twitch/callback');
    $resp->assertRedirect('/login');
    $resp->assertSessionHas('error');
});

// Helper for SocialiteUser mock
function mockSocialiteUser($id, $display_name, $email)
{
    $u = mock(SocialiteUser::class);
    $u->id = $id;
    $u->nickname = $display_name;
    $u->name = $display_name;
    $u->email = $email;
    $u->avatar = 'http://img';
    $u->user = [
        'display_name' => $display_name,
        'profile_image_url' => 'http://img',
        'broadcaster_type' => 'a',
        'created_at' => now()->toIsoString(),
        'description' => 'desc',
    ];
    $u->shouldReceive('getId')->andReturn($id);

    return $u;
}
