<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mode démonstration
    |--------------------------------------------------------------------------
    |
    | Le mode démo permet d'accéder à l'application avec des comptes pré-remplis
    | pour chaque rôle, sans avoir à s'inscrire ni à valider de compte.
    |
    | Il est activé uniquement si :
    | - DEMO_MODE=true dans .env
    | - APP_ENV différent de 'production'
    |
    */

    'enabled' => env('DEMO_MODE', false) && env('APP_ENV') !== 'production',

    /*
    |--------------------------------------------------------------------------
    | Comptes de démonstration
    |--------------------------------------------------------------------------
    |
    | Définition des comptes de démonstration pour chaque rôle.
    | Les mots de passe sont 'demo123' pour tous les comptes.
    |
    */
    'accounts' => [
        'patient' => [
            'name' => 'Awa Diop (Patient Démo)',
            'email' => 'patient.demo@sunu-sante.sn',
            'password' => 'demo123',
            'role' => 'Patient',
            'statut_compte' => 'Actif',
        ],
        'secretaire' => [
            'name' => 'Fatou Sarr (Secrétaire Démo)',
            'email' => 'secretaire.demo@sunu-sante.sn',
            'password' => 'demo123',
            'role' => 'Secrétaire de service',
            'statut_compte' => 'Actif',
        ],
        'accueil' => [
            'name' => 'Moussa Diallo (Agent Démo)',
            'email' => 'accueil.demo@sunu-sante.sn',
            'password' => 'demo123',
            'role' => "Agent d'accueil",
            'statut_compte' => 'Actif',
        ],
        'medecin' => [
            'name' => 'Dr. Aminata Ndiaye (Médecin Démo)',
            'email' => 'medecin.demo@sunu-sante.sn',
            'password' => 'demo123',
            'role' => 'Médecin',
            'statut_compte' => 'Actif',
        ],
        'major' => [
            'name' => 'Cheikh Fall (Major Démo)',
            'email' => 'major.demo@sunu-sante.sn',
            'password' => 'demo123',
            'role' => 'Major de service',
            'statut_compte' => 'Actif',
        ],
        'admin' => [
            'name' => 'Admin Sunu (Admin Démo)',
            'email' => 'admin.demo@sunu-sante.sn',
            'password' => 'demo123',
            'role' => 'Administrateur',
            'statut_compte' => 'Actif',
        ],
    ],
];