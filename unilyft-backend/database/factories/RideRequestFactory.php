<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RideRequest>
 */
class RideRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = ['pending', 'accepted', 'rejected'];

        return [
            'userId'=>fake()->numberBetween(3, 12),
            'startLocation'=>fake()->address(),
            'destination'=>fake()->address(),
            'date'=>fake()->date(),
            'time'=>fake()->time(),
            'numberOfPassenger'=>fake()->numberBetween(1, 4),
            'description'=>fake()->sentence(),
            // 'status'=>fake()->randomElement($status),
        ];
    }
}
