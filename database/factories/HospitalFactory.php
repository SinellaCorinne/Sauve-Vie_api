<?php

namespace Database\Factories;

use App\Models\Hospital;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hospital>
 */
class HospitalFactory extends Factory
{
    private static array $algeriaCities = [
        'Alger', 'Oran', 'Constantine', 'Annaba', 'Blida',
        'Batna', 'Djelfa', 'Sétif', 'Sidi Bel Abbès', 'Biskra',
    ];

    private static array $hospitalPrefixes = [
        'CHU', 'EPH', 'Clinique', 'Centre Hospitalier', 'Polyclinique', 'Hôpital',
    ];

    public function definition(): array
    {
        $prefix = fake()->randomElement(self::$hospitalPrefixes);
        $city   = fake()->randomElement(self::$algeriaCities);

        return [
            'user_id'          => User::factory()->hospital(),
            'institution_name' => "{$prefix} de {$city}",
            'city'             => $city,
            'address'          => fake()->streetAddress() . ', ' . $city,
            'phone'            => '0' . fake()->numerify('#########'),
        ];
    }
}
