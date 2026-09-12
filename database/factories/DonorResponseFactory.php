<?php

namespace Database\Factories;

use App\Models\BloodRequest;
use App\Models\DonorResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonorResponse>
 */
class DonorResponseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'donor_id'         => User::factory()->eligibleDonor(),
            'blood_request_id' => BloodRequest::factory(),
            'status'           => fake()->randomElement(['pending', 'confirmed', 'declined']),
            'note'             => fake()->optional(0.3)->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => 'confirmed']);
    }
}
