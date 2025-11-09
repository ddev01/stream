<?php

namespace Database\Seeders;

use App\Models\TwitchUser;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
        ]);

        // Ensure fake Laravel user exists for system operations
        TwitchUser::firstOrCreate(
            ['twitch_id' => '0'],
            ['display_name' => 'fake_laravel_user']
        );
    }
}
