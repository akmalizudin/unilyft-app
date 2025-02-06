<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Carpool>
 */
class CarpoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    // protected $model = Carpool::class;
    public function definition(): array
    {
        $driverIds = [3, 4, 5, 6, 7];
        $requestorIds = [8, 9, 10, 11, 12];
        $locations = [
            'Cendekiawan Residency',
            'Amanah Residency',
            'Ilmu Residency',
            'Murni Residency',
            'COE Academic Building',
            'Admin Building (BA)',
            'BB Building',
            'BC Building',
            'BD Building',
            'BE Building',
            'BF Building',
            'BG Building',
            'BV Theater',
            'Upten Food Court',
            'Sport Arena',
            'Uniten Masjid',
            'Information Resource Centre'
        ];

        $isDriver = $this->faker->boolean();

        if ($isDriver) {
            $driverId = $this->faker->randomElement($driverIds);
            $requestorId = null;
            $availableSeats = $this->faker->numberBetween(1, 4);
            $numberOfPassenger = null;
            $description = "Hi I am looking for a carpooling partner that is going to the same direction as me. Feel free to join!";
        } else {
            $driverId = null;
            $requestorId = $this->faker->randomElement($requestorIds);
            $availableSeats = null;
            $numberOfPassenger = $this->faker->numberBetween(1, 4);
            $description = "Hi I am looking for anyone that is going to the same direction as me and is willing to provide the ride for me. Thank you in advance!";
        }
        do {
            $startLocation = $this->faker->randomElement($locations);
            $destination = $this->faker->randomElement($locations);
        } while ($startLocation === $destination);

        return [
            'driver_id' => $driverId,
            'requestor_id' => $requestorId,
            'start_location' => $startLocation,
            'destination' => $destination,
            'date' => $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'time' => $this->faker->time(),
            'available_seats' => $availableSeats,
            'number_of_passenger' => $numberOfPassenger,
            'description' => $description,
            'status' => 'Active',
        ];
    }
}
