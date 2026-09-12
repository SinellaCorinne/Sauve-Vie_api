<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // donor_responses : table de liaison entre un donneur et une demande.
    // Quand un donneur clique "je suis disponible" sur une demande, une ligne est créée ici.
    // Elle sert aussi d'historique des dons confirmés.
    public function up(): void
    {
        Schema::create('donor_responses', function (Blueprint $table) {
            $table->id();

            // Le donneur qui répond
            $table->foreignId('donor_id')->constrained('users')->cascadeOnDelete();

            // La demande à laquelle il répond
            $table->foreignId('blood_request_id')->constrained()->cascadeOnDelete();

            // Statut de la réponse :
            // pending   → le donneur a signalé sa disponibilité, en attente de confirmation hôpital
            // confirmed → l'hôpital a confirmé le don
            // declined  → l'hôpital ou le donneur a annulé
            $table->enum('status', ['pending', 'confirmed', 'declined'])->default('pending');

            // Note optionnelle du donneur (ex: "disponible à partir de 15h")
            $table->text('note')->nullable();

            // Contrainte d'unicité : un donneur ne peut répondre qu'une seule fois à une demande donnée
            $table->unique(['donor_id', 'blood_request_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donor_responses');
    }
};
