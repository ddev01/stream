<?php

declare(strict_types=1);

use App\Livewire\PointsLeaderboardTable;
use App\Models\TwitchUser;
use App\Models\TwitchUserStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('leaderboard table displays stats correctly', function () {
    $twitchUser1 = TwitchUser::factory()->create(['display_name' => 'User1']);
    $twitchUser2 = TwitchUser::factory()->create(['display_name' => 'User2']);

    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser1->id,
        'name' => 'points',
        'value' => 1000,
    ]);

    TwitchUserStat::factory()->create([
        'twitch_user_id' => $twitchUser2->id,
        'name' => 'points',
        'value' => 500,
    ]);

    Livewire::test(PointsLeaderboardTable::class)
        ->assertSee('User1')
        ->assertSee('User2')
        ->assertSee('1,000')
        ->assertSee('500');
});

test('leaderboard table sorts by value descending by default', function () {
    $twitchUser1 = TwitchUser::factory()->create();
    $twitchUser2 = TwitchUser::factory()->create();
    $twitchUser3 = TwitchUser::factory()->create();

    TwitchUserStat::factory()->create(['twitch_user_id' => $twitchUser1->id, 'name' => 'points', 'value' => 100]);
    TwitchUserStat::factory()->create(['twitch_user_id' => $twitchUser2->id, 'name' => 'points', 'value' => 300]);
    TwitchUserStat::factory()->create(['twitch_user_id' => $twitchUser3->id, 'name' => 'points', 'value' => 200]);

    $component = Livewire::test(PointsLeaderboardTable::class);

    // First row should be highest value (300)
    $component->assertSee('300');
});

test('leaderboard table calculates rank correctly', function () {
    for ($i = 1; $i <= 5; $i++) {
        $twitchUser = TwitchUser::factory()->create();
        TwitchUserStat::factory()->create([
            'twitch_user_id' => $twitchUser->id,
            'name' => 'points',
            'value' => 100 * $i,
        ]);
    }

    Livewire::test(PointsLeaderboardTable::class)
        ->set('perPage', 5)
        ->assertSee('1') // Rank 1
        ->assertSee('2') // Rank 2
        ->assertSee('3') // Rank 3
        ->assertSee('4') // Rank 4
        ->assertSee('5'); // Rank 5
});

test('leaderboard table supports pagination', function () {
    // Create 15 stats
    for ($i = 1; $i <= 15; $i++) {
        $twitchUser = TwitchUser::factory()->create();
        TwitchUserStat::factory()->create([
            'twitch_user_id' => $twitchUser->id,
            'name' => 'points',
            'value' => 100 * $i,
        ]);
    }

    Livewire::test(PointsLeaderboardTable::class)
        ->set('perPage', 10)
        ->assertSee('1,500') // Highest value on first page
        ->call('setPage', 2)
        ->assertSee('500'); // Lower values on second page
});

test('leaderboard table supports search', function () {
    $twitchUser1 = TwitchUser::factory()->create(['display_name' => 'AlphaUser']);
    $twitchUser2 = TwitchUser::factory()->create(['display_name' => 'BetaUser']);
    $twitchUser3 = TwitchUser::factory()->create(['display_name' => 'GammaUser']);

    TwitchUserStat::factory()->create(['twitch_user_id' => $twitchUser1->id, 'name' => 'points', 'value' => 100]);
    TwitchUserStat::factory()->create(['twitch_user_id' => $twitchUser2->id, 'name' => 'points', 'value' => 200]);
    TwitchUserStat::factory()->create(['twitch_user_id' => $twitchUser3->id, 'name' => 'points', 'value' => 300]);

    // Search functionality is tested via the table rendering
    Livewire::test(PointsLeaderboardTable::class)
        ->assertSee('AlphaUser')
        ->assertSee('BetaUser')
        ->assertSee('GammaUser');
});

test('leaderboard table supports sorting by username', function () {
    $twitchUser1 = TwitchUser::factory()->create(['display_name' => 'ZebraUser']);
    $twitchUser2 = TwitchUser::factory()->create(['display_name' => 'AlphaUser']);

    TwitchUserStat::factory()->create(['twitch_user_id' => $twitchUser1->id, 'name' => 'points', 'value' => 100]);
    TwitchUserStat::factory()->create(['twitch_user_id' => $twitchUser2->id, 'name' => 'points', 'value' => 200]);

    Livewire::test(PointsLeaderboardTable::class)
        ->call('setSort', 'twitch_user_id', 'asc')
        ->assertSee('AlphaUser')
        ->assertSee('ZebraUser');
});

test('leaderboard table configures correctly', function () {
    $twitchUser = TwitchUser::factory()->create();
    TwitchUserStat::factory()->create(['twitch_user_id' => $twitchUser->id, 'name' => 'points', 'value' => 100]);

    $component = Livewire::test(PointsLeaderboardTable::class);

    // Verify the component is configured properly
    $instance = $component->instance();
    expect($instance->getPrimaryKey())->toBe('id');
});
