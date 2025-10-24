<?php

declare(strict_types=1);

use App\Models\TwitchUser;
use App\Models\TwitchUserStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('c# posts stats then user signs in with oauth', function () {
    // Simulate C# import
    $stats = [
        ['userId' => '10', 'userName' => 'alpha', 'platform' => 'twitch', 'name' => 'points', 'value' => 77, 'lastWrite' => now()->toISOString()],
    ];
    $u = User::factory()->create(['email' => 'alpha@x.com']);
    $token = $u->createToken('test')->plainTextToken;
    $this->postJson('/api/twitch/stats', ['stats' => $stats], ['Authorization' => 'Bearer '.$token]);

    expect(TwitchUser::where('twitch_id', '10')->exists())->toBeTrue();
    // Now, sign in with OAuth (mock Socialite)
    \Laravel\Socialite\Facades\Socialite::shouldReceive('driver->user')->andReturn(test_socialite_user('10', 'alpha', 'alpha@x.com'));
    $this->get('/auth/twitch/callback')->assertRedirect('/dashboard');
    $tw = TwitchUser::where('twitch_id', '10')->first();
    expect($tw->display_name)->toBe('alpha')->and($tw->user_id)->toBe($u->id);
});

test('user signs in then c# posts stats', function () {
    $u = User::factory()->create(['email' => 'beta@x.com']);
    \Laravel\Socialite\Facades\Socialite::shouldReceive('driver->user')->andReturn(test_socialite_user('20', 'beta', 'beta@x.com'));
    $this->get('/auth/twitch/callback')->assertRedirect('/dashboard');
    $token = $u->createToken('test')->plainTextToken;
    $stats = [
        ['userId' => '20', 'userName' => 'beta', 'platform' => 'twitch', 'name' => 'watchtime', 'value' => 123, 'lastWrite' => now()->toISOString()],
    ];
    $this->postJson('/api/twitch/stats', ['stats' => $stats], ['Authorization' => 'Bearer '.$token]);
    $tw = TwitchUser::where('twitch_id', '20')->first();
    expect($tw->display_name)->toBe('beta')->and($tw->getStat('watchtime'))->toBe('123');
});

test('stats persist through oauth enrichment', function () {
    $tw = TwitchUser::factory()->create(['twitch_id' => '42']);
    TwitchUserStat::factory()->create(['twitch_user_id' => $tw->id, 'name' => 'x', 'value' => 42]);
    \Laravel\Socialite\Facades\Socialite::shouldReceive('driver->user')->andReturn(test_socialite_user('42', 'gamma', 'g@g.com'));
    $this->get('/auth/twitch/callback')->assertRedirect('/dashboard');
    $tw->refresh();
    expect($tw->getStat('x'))->toBe('42');
});

test('multiple oauth sign ins dont duplicate data', function () {
    \Laravel\Socialite\Facades\Socialite::shouldReceive('driver->user')->andReturn(test_socialite_user('66', 'multi', 'multi@x.com'));
    $this->get('/auth/twitch/callback')->assertRedirect('/dashboard');
    $this->get('/auth/twitch/callback')->assertRedirect('/dashboard');
    expect(TwitchUser::where('twitch_id', '66')->count())->toBe(1);
    expect(User::where('email', 'multi@x.com')->count())->toBe(1);
});

function test_socialite_user($id, $display, $email)
{
    $mock = mock(\Laravel\Socialite\Two\User::class);
    $mock->id = $id;
    $mock->nickname = $display;
    $mock->name = $display;
    $mock->email = $email;
    $mock->avatar = 'pic';
    $mock->user = [
        'display_name' => $display,
        'profile_image_url' => 'pic',
        'broadcaster_type' => 'b',
        'created_at' => now()->toIsoString(),
        'description' => 'testdesc',
    ];
    $mock->shouldReceive('getId')->andReturn($id);

    return $mock;
}
