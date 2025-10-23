<?php

namespace Database\Factories;

use App\Models\TwitchUser;
use App\Models\TwitchUserStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating TwitchUserStat test instances
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TwitchUserStat>
 */
class TwitchUserStatFactory extends Factory
{
    protected $model = TwitchUserStat::class;

    /**
     * Define the model's default state
     */
    public function definition(): array
    {
        return [
            'twitch_user_id' => TwitchUser::factory(),
            'name' => fake()->randomElement(['points', 'watchtime', 'lurkCount', 'lurkTime', 'topThreeCount', 'hotPotatoesPassed']),
            'value' => fake()->numberBetween(0, 10000),
            'last_write' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the stat is for points
     */
    public function points(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'points',
            'value' => fake()->numberBetween(0, 100000),
        ]);
    }

    /**
     * Indicate that the stat is for watchtime
     */
    public function watchtime(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'watchtime',
            'value' => fake()->numberBetween(0, 5000000),
        ]);
    }

    /**
     * Indicate that the stat is for lurk count
     */
    public function lurkCount(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'lurkCount',
            'value' => fake()->numberBetween(0, 100),
        ]);
    }
}
