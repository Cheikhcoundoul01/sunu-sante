<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Six rôles et permissions selon AGENTS.md section 5.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Comptes patients
            'patients.view',
            'patients.valider',
            'patients.rejeter',

            // Rendez-vous
            'rdv.voir',
            'rdv.demander',
            'rdv.modifier.own',
            'rdv.annuler.own',
            'rdv.valider',
            'rdv.rejeter',

            // Disponibilités et salles
            'disponibilites.gerer',
            'salles.gerer',

            // File d'attente
            'file.voir',
            'file.gerer',
            'file.priorite',

            // Paramètres / utilisateurs
            'services.gerer',
            'users.gerer',
            'horizon.gerer',

            // Statistiques
            'stats.service',
            'stats.global',

            // Profil
            'profil.gerer',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            'Patient',
            'Secrétaire de service',
            "Agent d'accueil",
            'Médecin',
            'Major de service',
            'Administrateur',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        Role::findByName('Patient')->syncPermissions([
            'rdv.voir',
            'rdv.demander',
            'rdv.modifier.own',
            'rdv.annuler.own',
            'profil.gerer',
        ]);

        Role::findByName('Secrétaire de service')->syncPermissions([
            'patients.view',
            'patients.valider',
            'patients.rejeter',
            'rdv.voir',
            'rdv.valider',
            'rdv.rejeter',
            'disponibilites.gerer',
            'salles.gerer',
            'profil.gerer',
        ]);

        Role::findByName("Agent d'accueil")->syncPermissions([
            'file.voir',
            'file.gerer',
            'file.priorite',
            'profil.gerer',
        ]);

        Role::findByName('Médecin')->syncPermissions([
            'rdv.voir',
            'file.voir',
            'profil.gerer',
        ]);

        Role::findByName('Major de service')->syncPermissions([
            'stats.service',
            'rdv.voir',
            'profil.gerer',
        ]);

        Role::findByName('Administrateur')->syncPermissions(Permission::all());
    }
}
