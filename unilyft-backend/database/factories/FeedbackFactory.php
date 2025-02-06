<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feedback>
 */
class FeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'carpoolId'=>fake()->numberBetween(1, 10),
            'authorId'=>fake()->numberBetween(3, 12),
            'rating'=>fake()->numberBetween(1, 5),
            'comment'=>fake()->sentence(),
            'date'=>fake()->date(),
        ];
    }
}
