<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Note : le champ « salle » (rendez-vous d'intervention) relève de l'étape 9
     * et sera ajouté par migration corrective. Ici, uniquement les champs
     * nécessaires, notamment le calcul des créneaux libres (étape 6).
     */
    public function up(): void
    {
        Schema::create('rendez_vouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('medecin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->date('date');
            $table->time('heure_debut');
            $table->unsignedInteger('duree_prevue')->nullable();
            // Statuts : Demandé, Validé, Annulé (AGENTS.md §6)
            $table->string('statut', 20);
            $table->string('motif')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['medecin_id', 'date']);
            $table->index(['service_id', 'date']);
            $table->index(['patient_id', 'date']);
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rendez_vouses');
    }
};
