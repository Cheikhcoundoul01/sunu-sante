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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('traite_par_id')
                ->nullable()
                ->after('statut_compte')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('traite_at')->nullable()->after('traite_par_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('traite_par_id');
            $table->dropColumn('traite_at');
        });
    }
};
