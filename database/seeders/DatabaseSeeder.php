<?php

namespace Database\Seeders;

use App\Models\BloodRequest;
use App\Models\DonorResponse;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Données créées :
     *  - 1 donneur de test (email/password connus)
     *  - 1 hôpital de test (email/password connus)
     *  - 30 donneurs aléatoires (variés groupes, disponibilités)
     *  - 5 hôpitaux avec profil + demandes ouvertes
     *  - Quelques réponses de donneurs à des demandes
     */
    public function run(): void
    {
        // ── Compte de test Donneur ───────────────────────────────────────────
        $testDonor = User::create([
            'name'               => 'Ahmed Bensalem',
            'email'              => 'donor@test.com',
            'password'           => Hash::make('password'),
            'role'               => 'donor',
            'blood_type'         => 'O-',
            'phone'              => '0555123456',
            'city'               => 'Alger',
            'is_available'       => true,
            'last_donation_date' => null,
        ]);

        // ── Compte de test Hôpital ───────────────────────────────────────────
        $testHospitalUser = User::create([
            'name'     => 'CHU Mustapha Bacha',
            'email'    => 'hospital@test.com',
            'password' => Hash::make('password'),
            'role'     => 'hospital',
        ]);

        Hospital::create([
            'user_id'          => $testHospitalUser->id,
            'institution_name' => 'CHU Mustapha Bacha',
            'city'             => 'Alger',
            'address'          => '1 Place du 1er Mai, Alger',
            'phone'            => '021739301',
        ]);

        // Quelques demandes pour l'hôpital de test
        $urgentRequest = BloodRequest::create([
            'hospital_id'   => $testHospitalUser->id,
            'blood_type'    => 'O-',
            'urgency_level' => 'critical',
            'description'   => 'Patient polytraumatisé admis aux urgences, besoin immédiat.',
            'city'          => 'Alger',
            'status'        => 'open',
            'expires_at'    => now()->addHours(12),
        ]);

        BloodRequest::create([
            'hospital_id'   => $testHospitalUser->id,
            'blood_type'    => 'A+',
            'urgency_level' => 'urgent',
            'description'   => 'Opération chirurgicale programmée demain matin.',
            'city'          => 'Alger',
            'status'        => 'open',
            'expires_at'    => now()->addDays(2),
        ]);

        // Réponse du donneur de test à la demande critique
        DonorResponse::create([
            'donor_id'         => $testDonor->id,
            'blood_request_id' => $urgentRequest->id,
            'status'           => 'pending',
            'note'             => 'Disponible immédiatement, je suis à Alger.',
        ]);

        // ── Donneurs aléatoires ──────────────────────────────────────────────
        User::factory(30)->eligibleDonor()->create();
        User::factory(20)->create(); // mix disponible/indisponible

        // ── Hôpitaux aléatoires avec profils + demandes ──────────────────────
        User::factory(5)
            ->hospital()
            ->create()
            ->each(function (User $hospitalUser) {
                // Création du profil hôpital
                Hospital::factory()->create(['user_id' => $hospitalUser->id]);

                // 2 à 4 demandes ouvertes par hôpital
                $city = $hospitalUser->hospital->city;
                BloodRequest::factory(rand(2, 4))
                    ->create([
                        'hospital_id' => $hospitalUser->id,
                        'city'        => $city,
                    ]);

                // 1 demande satisfaite par hôpital (historique)
                BloodRequest::factory()->fulfilled()->create([
                    'hospital_id' => $hospitalUser->id,
                    'city'        => $city,
                ]);
            });

        $this->command->info('✓ Base de données peuplée avec succès.');
        $this->command->line('  Donneur test  : donor@test.com / password');
        $this->command->line('  Hôpital test  : hospital@test.com / password');
    }
}
