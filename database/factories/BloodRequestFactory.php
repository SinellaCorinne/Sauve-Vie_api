<?php

namespace Database\Factories;

use App\Models\BloodRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BloodRequest>
 */
class BloodRequestFactory extends Factory
{
    private static array $bloodTypes     = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    private static array $urgencyLevels  = ['normal', 'urgent', 'critical'];
    private static array $algeriaCities  = [
        'Alger', 'Oran', 'Constantine', 'Annaba', 'Blida', 'Batna', 'Sétif',
    ];

    private static array $descriptions = [
        'Besoin urgent pour une opération chirurgicale programmée.',
        'Patient en urgence vitale, transfusion nécessaire immédiatement.',
        'Stock critique pour le service de maternité.',
        'Intervention chirurgicale lourde prévue dans 48h.',
        'Patient polytraumatisé admis aux urgences.',
        'Traitement oncologique nécessitant transfusion régulière.',
        null,
    ];

    public function definition(): array
    {
        $expiresAt = fake()->boolean(70)
            ? fake()->dateTimeBetween('now', '+7 days')
            : null;

        return [
            'hospital_id'   => User::factory()->hospital(),
            'blood_type'    => fake()->randomElement(self::$bloodTypes),
            'urgency_level' => fake()->randomElement(self::$urgencyLevels),
            'description'   => fake()->randomElement(self::$descriptions),
            'city'          => fake()->randomElement(self::$algeriaCities),
            'status'        => 'open',
            'expires_at'    => $expiresAt,
        ];
    }

    /** State : demande urgente ouverte */
    public function urgent(): static
    {
        return $this->state(fn () => [
            'urgency_level' => 'urgent',
            'status'        => 'open',
            'expires_at'    => fake()->dateTimeBetween('now', '+3 days'),
        ]);
    }

    /** State : demande critique ouverte */
    public function critical(): static
    {
        return $this->state(fn () => [
            'urgency_level' => 'critical',
            'status'        => 'open',
            'expires_at'    => fake()->dateTimeBetween('now', '+24 hours'),
        ]);
    }

    /** State : demande satisfaite */
    public function fulfilled(): static
    {
        return $this->state(fn () => [
            'status' => 'fulfilled',
        ]);
    }
}
