<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    private static array $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    private static array $algeriaCities = [
        'Alger', 'Oran', 'Constantine', 'Annaba', 'Blida',
        'Batna', 'Djelfa', 'Sétif', 'Sidi Bel Abbès', 'Biskra',
        'Tébessa', 'El Oued', 'Skikda', 'Tiaret', 'Béjaïa',
        'Tlemcen', 'Bordj Bou Arréridj', 'Béchar', 'Mostaganem', 'Médéa',
    ];

    public function definition(): array
    {
        return [
            'name'               => fake()->name(),
            'email'              => fake()->unique()->safeEmail(),
            'email_verified_at'  => now(),
            'password'           => static::$password ??= Hash::make('password'),
            'role'               => 'donor',
            'blood_type'         => fake()->randomElement(self::$bloodTypes),
            'phone'              => '0' . fake()->numerify('#########'),
            'city'               => fake()->randomElement(self::$algeriaCities),
            'is_available'       => fake()->boolean(75), // 75% disponibles
            'last_donation_date' => fake()->optional(0.6)->dateTimeBetween('-18 months', 'now'),
            'remember_token'     => Str::random(10),
        ];
    }

    /** State : donneur éligible (disponible + pas donné depuis 3 mois) */
    public function eligibleDonor(): static
    {
        return $this->state(fn () => [
            'role'               => 'donor',
            'is_available'       => true,
            'last_donation_date' => fake()->optional(0.4)->dateTimeBetween('-18 months', '-3 months'),
        ]);
    }

    /** State : hôpital (sans données donneur) */
    public function hospital(): static
    {
        return $this->state(fn () => [
            'role'               => 'hospital',
            'blood_type'         => null,
            'phone'              => null,
            'city'               => null,
            'is_available'       => false,
            'last_donation_date' => null,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
