<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // blood_requests : les demandes urgentes publiées par les hôpitaux.
    // C'est la table centrale du système — elle déclenche le matching.
    public function up(): void
    {
        Schema::create('blood_requests', function (Blueprint $table) {
            $table->id();

            // L'hôpital qui publie la demande (via son user_id)
            $table->foreignId('hospital_id')->constrained('users')->cascadeOnDelete();

            // Groupe sanguin recherché
            $table->enum('blood_type', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']);

            // Niveau d'urgence — influence l'affichage côté frontend (badge couleur)
            $table->enum('urgency_level', ['normal', 'urgent', 'critical'])->default('urgent');

            // Description libre : contexte médical, quantité nécessaire, etc.
            $table->text('description')->nullable();

            // Ville de l'hôpital au moment de la demande (dénormalisé pour performance)
            $table->string('city', 100)->nullable();

            // Statut du cycle de vie de la demande :
            // open     → active, les donneurs compatibles sont notifiés
            // fulfilled → satisfaite par l'hôpital (clôturée manuellement)
            // expired  → passée sans être satisfaite (via job schedulé ou manuellement)
            $table->enum('status', ['open', 'fulfilled', 'expired'])->default('open');

            // Date limite souhaitée — au-delà, la demande peut être marquée 'expired'
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_requests');
    }
};
