<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiTokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a dedicated API user for the C# application
        $apiUser = User::firstOrCreate(
            ['email' => 'api@streamerbot.local'],
            [
                'name' => 'StreamerBot API User',
                'password' => Hash::make(Str::random(32)), // Random password since we'll use tokens
            ]
        );

        // Create a personal access token for the C# application
        $token = $apiUser->createToken('streamerbot-poster', ['*']);

        $this->command->info('API Token created successfully!');
        $this->command->info('Token: '.$token->plainTextToken);
        $this->command->info('Save this token securely - it will not be shown again!');
        $this->command->info('Add this token to your C# application configuration.');
    }
}
