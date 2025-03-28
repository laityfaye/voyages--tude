<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  // database/migrations/xxxx_xx_xx_create_utilisateurs_table.php
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();
            $table->string('matricule')->unique();
            $table->date('dateRecrutement');
            $table->string('etablissement');
            $table->string('departement');
            $table->string('laboratoire');
            $table->string('responsableLaboratoire');
            $table->text('themesRecherche')->nullable();
            $table->text('partenariatsReseauxRecherche')->nullable();
            $table->string('diplome');
            $table->date('dateObtentionDiplome');
            $table->text('fonctionsOccupees')->nullable();
            $table->text('publicationsRecentes')->nullable();
            $table->string('telephoneProfessionnel');
            $table->string('telephonePersonnel')->nullable();
            $table->text('adresseProfessionnelle');
            $table->text('adressePersonnelle')->nullable();
            $table->boolean('estActif')->default(true);
            $table->string('password');
            $table->timestamps();
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
