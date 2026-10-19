<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! config('demo.enabled')) {
            $this->command->info('Mode démo désactivé, DemoSeeder ignoré.');

            return;
        }

        $this->command->info('Création des comptes de démonstration...');

        // S'assurer que les services existent
        $services = [
            'Cardiologie' => Service::firstOrCreate(['nom' => 'Cardiologie'], ['actif' => true]),
            'Pédiatrie' => Service::firstOrCreate(['nom' => 'Pédiatrie'], ['actif' => true]),
            'Gynécologie' => Service::firstOrCreate(['nom' => 'Gynécologie'], ['actif' => true]),
            'Médecine générale' => Service::firstOrCreate(['nom' => 'Médecine générale'], ['actif' => true]),
            'Chirurgie' => Service::firstOrCreate(['nom' => 'Chirurgie'], ['actif' => true]),
        ];

        $demoAccounts = config('demo.accounts');
        $password = Hash::make('demo123');

        foreach ($demoAccounts as $key => $account) {
            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => $password,
                    'statut_compte' => $account['statut_compte'],
                    'is_demo' => true,
                ]
            );

            $user->syncRoles([$account['role']]);

            // Assigner un service pour le personnel de service
            if (in_array($account['role'], ['Secrétaire de service', "Agent d'accueil", 'Médecin', 'Major de service', 'Administrateur'])) {
                $service = $services['Cardiologie'];
                $user->update(['service_id' => $service->id]);
            }
        }

        $this->command->info('Comptes de démonstration créés avec succès (mot de passe : demo123).');
    }
}