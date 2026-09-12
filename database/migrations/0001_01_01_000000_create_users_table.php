<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Rôle : donneur ou hôpital — détermine ce que l'utilisateur peut faire
            $table->enum('role', ['donor', 'hospital'])->default('donor');

            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // ── Champs spécifiques aux donneurs ──────────────────────────────
            // Groupe sanguin ABO + Rhésus (ex: A+, O-, AB+)
            $table->enum('blood_type', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();

            // Numéro de téléphone pour contacter le donneur en urgence
            $table->string('phone', 20)->nullable();

            // Ville du donneur (pour filtrage géographique futur)
            $table->string('city', 100)->nullable();

            // Le donneur est-il disponible pour donner maintenant ?
            $table->boolean('is_available')->default(true);

            // Date du dernier don — on exclut les donneurs ayant donné il y a moins de 3 mois
            $table->date('last_donation_date')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
