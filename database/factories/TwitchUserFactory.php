<?php

namespace Database\Factories;

use App\Models\TwitchUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for creating TwitchUser test instances
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TwitchUser>
 */
class TwitchUserFactory extends Factory
{
    protected $model = TwitchUser::class;

    /**
     * Define the model's default state
     */
    public function definition(): array
    {
        return [
            'twitch_id' => fake()->unique()->numerify('########'),
            'display_name' => fake()->userName(),
            'role' => fake()->numberBetween(1, 4),
            'subscribed' => fake()->boolean(30),
            'type' => 'twitch',
            'present' => fake()->boolean(50),
            'last_active' => fake()->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Indicate that the Twitch user is linked to a Laravel user
     */
    public function linked(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory(),
        ]);
    }

    /**
     * Indicate that the Twitch user has OAuth-enriched data
     */
    public function enriched(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_image_url' => fake()->imageUrl(300, 300, 'people'),
            'broadcaster_type' => fake()->randomElement(['', 'affiliate', 'partner']),
            'description' => fake()->sentence(),
            'twitch_created_at' => fake()->dateTimeBetween('-5 years', '-1 year'),
            'email' => fake()->unique()->safeEmail(),
        ]);
    }

    /**
     * Indicate that the Twitch user is subscribed
     */
    public function subscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscribed' => true,
        ]);
    }

    /**
     * Indicate that the Twitch user is present
     */
    public function present(): static
    {
        return $this->state(fn (array $attributes) => [
            'present' => true,
        ]);
    }
}
