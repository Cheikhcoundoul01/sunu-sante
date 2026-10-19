<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DemoReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Réinitialise les données de démonstration (utilisateurs is_demo=true) sans toucher aux données réelles.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! config('demo.enabled')) {
            $this->error('Le mode démo n\'est pas activé (DEMO_MODE=true et APP_ENV != production).');

            return 1;
        }

        $this->info('Réinitialisation des données de démonstration...');

        // Supprimer les utilisateurs de démo
        $deleted = User::where('is_demo', true)->delete();

        $this->info("{$deleted} utilisateur(s) de démonstration supprimé(s).");

        // Relancer le seeder de démo
        $this->call('db:seed', ['--class' => 'Database\Seeders\DemoSeeder']);

        $this->info('Données de démonstration réinitialisées avec succès.');

        return 0;
    }
}