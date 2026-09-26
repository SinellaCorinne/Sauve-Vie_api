<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_donor_can_fetch_compatible_open_requests(): void
    {
        $donor = User::factory()->create([
            'role' => 'donor',
            'blood_type' => 'A-',
            'is_available' => true,
            'last_donation_date' => now()->subMonths(4),
        ]);

        $hospital = User::factory()->hospital()->create();

        $compatibleRequest = BloodRequest::factory()->create([
            'hospital_id' => $hospital->id,
            'blood_type' => 'A-',
            'status' => 'open',
            'expires_at' => now()->addDay(),
        ]);

        BloodRequest::factory()->create([
            'hospital_id' => $hospital->id,
            'blood_type' => 'B+',
            'status' => 'open',
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($donor, 'sanctum')->getJson('/api/donor/compatible-requests');

        $response->assertOk();
        $response->assertJsonPath('donor_blood_type', 'A-');
        $this->assertCount(1, $response->json('requests.data'));
        $this->assertSame($compatibleRequest->id, $response->json('requests.data.0.id'));
    }
}
