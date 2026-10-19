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
        Schema::table('rendez_vouses', function (Blueprint $table) {
            $table->foreignId('salle_id')
                ->nullable()
                ->after('service_id')
                ->constrained('salles')
                ->nullOnDelete();
            $table->index('salle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rendez_vouses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('salle_id');
        });
    }
};