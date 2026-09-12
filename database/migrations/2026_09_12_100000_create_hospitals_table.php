<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // La table hospitals stocke le profil étendu d'un utilisateur de type 'hospital'.
    // On utilise une table séparée plutôt que d'encombrer la table users avec des
    // colonnes nullables pour les hôpitaux — séparation claire des deux rôles.
    public function up(): void
    {
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();

            // Relation 1-1 avec users : chaque hôpital correspond à un compte utilisateur
            // onDelete('cascade') : si le compte est supprimé, le profil hôpital l'est aussi
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // Nom officiel de l'établissement (peut différer du nom du compte)
            $table->string('institution_name');

            // Adresse complète de l'hôpital
            $table->string('address')->nullable();

            // Ville — utilisée pour le filtrage géographique des donneurs
            $table->string('city', 100);

            // Numéro de téléphone de l'établissement
            $table->string('phone', 20)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitals');
    }
};
