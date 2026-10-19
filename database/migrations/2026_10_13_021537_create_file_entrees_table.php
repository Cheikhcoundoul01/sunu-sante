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
        Schema::create('file_entrees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('rendez_vous_id')->constrained('rendez_vouses')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->enum('statut', ['En attente', 'En cours', 'Terminé', 'Absent'])->default('En attente');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('priorite')->default(false);
            $table->text('motif_priorite')->nullable();
            $table->timestamps();

            $table->index(['service_id', 'date']);
            $table->index(['patient_id', 'date']);
            $table->index('statut');
            $table->unique('rendez_vous_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_entrees');
    }
};