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
        Schema::create('demande_voyages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date_demande');
            $table->string('destination');
            $table->string('ville_pays_final');
            $table->text('itineraire');
            $table->string('etablissement_accueil');
            $table->string('responsable_accueil');
            $table->string('statut');
            $table->text('commentaire')->nullable();
            $table->date('date_validation')->nullable();
            $table->integer('annee_dernier_voyage')->nullable();
            $table->boolean('mention_lu_et_approuve')->default(false);
            $table->date('date_engagement')->nullable();
            $table->string('nom_chef_etablissement');
            $table->date('date_visa_etablissement')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demande_voyages');
    }
};
