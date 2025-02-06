<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RideOffer>
 */
class RideOfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = ['pending', 'approved', 'rejected'];
        
        return [
            'userId'=>fake()->numberBetween(3, 12),
            'startLocation'=>fake()->address(),
            'destination'=>fake()->address(),
            'date'=>fake()->date(),
            'time'=>fake()->time(),
            'availableSeats'=>fake()->numberBetween(1, 4),
            'description'=>fake()->sentence(),
            // 'status'=>fake()->randomElement($status),
        ];
    }
}
