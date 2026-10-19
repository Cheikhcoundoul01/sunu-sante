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
        Schema::create('disponibilite_salles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salle_id')->constrained('salles')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->tinyInteger('jour_semaine')->unsigned(); // 1=Lundi ... 7=Dimanche
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['salle_id', 'jour_semaine']);
            $table->index(['service_id', 'jour_semaine']);
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disponibilite_salles');
    }
};