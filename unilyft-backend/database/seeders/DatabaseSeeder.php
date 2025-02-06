<?php

namespace Database\Seeders;

use App\Models\Carpool;
use App\Models\CarpoolPassenger;
use App\Models\Feedback;
use App\Models\RideOffer;
use App\Models\RideRequest;
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
        $vehicleModels = ['Toyota Camry', 'Toyota Corolla', 'Honda Civic', 'Honda Accord', 'Kia Altima', 'Proton Saga', 'Nissan Sentra', 'Mazda3', 'Subaru Impreza', 'Hyundai Elantra'];
        $vehicleYears = ['2020', '2021', '2022', '2023', '2024', '2025', '2019', '2018', '2017', '2016'];
        $registrationNumbers = ['JRG7468', 'BMA7469', 'WYM7470', 'VGD7471', 'BQU7472', 'KLM7473', 'PQR7474', 'STU7475', 'VWX7476', 'YZA7477'];

        // create 2 user with role admin
        for ($i = 1; $i < 3; $i++) {
            User::factory()->create([
                'name' => 'Admin ' . $i,
                'email' => 'admin' . $i . '@unilyft.com',
                'role' => 'admin',
                'isVerified' => true,
                'vehicle_model' => $vehicleModels[array_rand($vehicleModels)],
                'vehicle_year' => $vehicleYears[array_rand($vehicleYears)],
                'registration_number' => $registrationNumbers[array_rand($registrationNumbers)],
            ]);
        }

        // create 10 user with role user
        $users = User::factory(10)->create();  //to create user fake data

        foreach ($users as $user) {
            if ($user->id >= 3 && $user->id <= 7) {
                $user->update([
                    'isVerified' => true,
                    'vehicle_model' => $vehicleModels[array_rand($vehicleModels)],
                    'vehicle_year' => $vehicleYears[array_rand($vehicleYears)],
                    'registration_number' => $registrationNumbers[array_rand($registrationNumbers)],
                ]);
            }
        }

        // create specific users "Akmal 1" and "Akmal 2"
        for ($i = 1; $i <= 2; $i++) {
            User::factory()->create([
                'name' => 'Test User ' . $i,
                'email' => 'testuser' . $i . '@student.uniten.edu.my',
                'role' => 'user',
                'isVerified' => false,
                // 'vehicle_model' => $vehicleModels[array_rand($vehicleModels)],
                // 'vehicle_year' => $vehicleYears[array_rand($vehicleYears)],
                // 'registration_number' => $registrationNumbers[array_rand($registrationNumbers)],
            ]);
        }

        Carpool::factory(10)->create();
        // Feedback::factory(10)->create();
    }
}
