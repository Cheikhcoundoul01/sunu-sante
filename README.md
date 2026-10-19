# Sunu Santé

Plateforme web de gestion des rendez-vous médicaux et de file d'attente dynamique — Cas d'étude : Hôpital Fann, Dakar.

Développée dans le cadre d'un PFE. À destination d'un usage pédagogique et de démonstration.

## 📋 Fonctionnalités

### Espace Patient
- Inscription avec compte en attente de validation
- Prise de rendez-vous (vérification de créneau libre, transaction)
- Modification/annulation de ses propres rendez-vous
- Suivi de sa position dans la file d'attente
- Consultation de son profil
- Interface mobile-first

### Espace Secrétaire de service
- Validation/rejet des comptes patients (première décision définitive)
- Validation/rejet des demandes de rendez-vous
- Gestion des disponibilités des médecins
- Gestion des salles d'intervention
- Suivi des rendez-vous

### Espace Agent d'accueil
- Gestion de la file d'attente du jour (uniquement cet acteur)
- Transitions de statut : En attente → En cours → Terminé, En attente → Absent, Absent → En attente
- Passage prioritaire avec motif obligatoire

### Espace Médecin
- Consultation de son planning
- Liste des patients du jour
- Accès restreint aux actions sur la file (consultation seule)

### Espace Major de service
- Consultation des statistiques de son service

### Espace Administrateur
- Création des comptes du personnel
- Gestion des services
- Paramétrage de l'horizon de réservation
- Consultation des statistiques globales

### Fonctionnalités transverses
- File d'attente par service et par jour (entrée automatique des RDV Validés sans salle, ordonnés par créneau)
- Salles d'intervention avec vérification d'absence de chevauchement
- Notifications par email uniquement (français)
- Journalisation d'activité (7 événements)
- Mode démonstration avec accès direct par rôle

## 🛠 Stack technique

- **Backend** : Laravel 12, PHP 8.2+
- **Frontend** : Livewire 3, Tailwind CSS v4, Alpine.js, Vite
- **Base de données** : MySQL/MariaDB (via XAMPP) ou SQLite (tests)
- **Rôles/Permissions** : Spatie Laravel-Permission
- **Journalisation** : Spatie Laravel-Activitylog
- **Temps réel** : Livewire `wire:poll` (Laravel Reverb optionnel)
- **Typographies** : @fontsource/inter (hébergées localement)
- **Tests** : PHPUnit 11
- **Build** : Vite + @tailwindcss/vite

## 🚀 Installation sous Windows (XAMPP)

### Prérequis
- [XAMPP](https://www.apachefriends.org/) avec PHP 8.2+
- [Composer](https://getcomposer.org/) 2.x
- [Node.js](https://nodejs.org/) 20+ et npm 10+
- Git

### Étape 1 : Cloner le dépôt
```powershell
cd C:\xampp\htdocs
git clone <URL_DU_DEPOT> sunu-sante
cd sunu-sante
```

### Étape 2 : Installer les dépendances PHP
```powershell
composer install
```

### Étape 3 : Configurer l'environnement
```powershell
copy .env.example .env
php artisan key:generate
```

Ouvrir `.env` et ajuster la connexion MySQL (valeurs par défaut XAMPP) :
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sunu_sante
DB_USERNAME=root
DB_PASSWORD=
```

### Étape 4 : Créer la base de données
Ouvrir phpMyAdmin ou MySQL via XAMPP et créer la base :

```sql
CREATE DATABASE sunu_sante CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Étape 5 : Exécuter migrations et seeders
```powershell
php artisan migrate
php artisan db:seed --class=RolePermissionSeeder
```

### Étape 6 : Installer dépendances frontend et build
```powershell
npm install
npm run build
```

### Étape 7 : Lancer l'application
Via le serveur intégré Laravel :
```powershell
php artisan serve
```

Puis accéder à : http://127.0.0.1:8000

Ou via XAMPP : http://localhost/sunu-sante/public

## 🎭 Mode démonstration

Le mode démo est désactivé par défaut. Il est activé **uniquement** si :
- `DEMO_MODE=true` dans `.env`
- `APP_ENV` différent de `production`

```powershell
# 1. Activer dans .env
DEMO_MODE=true
APP_ENV=local

# 2. Créer/actualiser les comptes démo
php artisan db:seed --class=DemoSeeder

# 3. Accès direct par rôle
# /demo/login/patient | secretaire | accueil | medecin | major | admin
# Mot de passe : demo123
```

Comptes démo (à titre indicatif, issus de `config/demo.php`) :
- `patient.demo@sunu-sante.sn` (Patient)
- `secretaire.demo@sunu-sante.sn` (Secrétaire de service)
- `accueil.demo@sunu-sante.sn` (Agent d'accueil)
- `medecin.demo@sunu-sante.sn` (Médecin)
- `major.demo@sunu-sante.sn` (Major de service)
- `admin.demo@sunu-sante.sn` (Administrateur)

**Réinitialisation sécurisée** (ne touche que les utilisateurs marqués `is_demo=true`) :
```powershell
php artisan demo:reset
```

Note : `demo:reset` refuse de s'exécuter si `DEMO_MODE` est inactif ou si `APP_ENV=production`. Jamais `migrate:fresh`.

## 🧪 Tests

### Tests unitaires et fonctionnels (SQLite :memory:)
Configuration par défaut : `phpunit.xml` (DB sqlite :memory:, MAIL_MAILER=array, etc.)
```powershell
php artisan test
# ou
vendor\bin\phpunit
```

### Tests avec MySQL réel
Configuration : `phpunit.mysql.xml` (DB `sunu_sante_test`, connexion `mysql_test`).
Prérequis : créer la base de test.
```powershell
php vendor\bin\phpunit -c phpunit.mysql.xml
```

**Statut actuel** : 205 tests, 791 assertions — tous passent (valeur à vérifier lors d'une exécution locale).

## 📦 Déploiement (recommandations)

- `APP_ENV=production`, `APP_DEBUG=false`, `DEMO_MODE=false`
- `npm run build` + `php artisan config:cache`, `route:cache`, `view:cache`
- Migrations : `php artisan migrate --force`
- Ne pas activer le mode démo en production
- Si images emails via URL HTTPS publique : à configurer lors du déploiement (aucune URL publique n'est codée)

## 🔐 Sécurité & bonnes pratiques

- Mots de passe hachés (bcrypt)
- Vériification des autorisations côté serveur (Policies/Gates) + masquage UI
- Protection CSRF, XSS, requêtes préparées (Eloquent)
- Rate-limiting sur tentative de connexion
- Middleware `account.active` : bloque comptes non Actifs
- Middleware `EnsureDemoMode` : protège routes démo en production
- `demo:reset` n'affecte que les données `is_demo=true`
- Emails transactionnels uniquement (aucun SMS/WhatsApp)
- Aucune donnée personnelle/sensible dans les logs
- Jamais exposer `.env` ou secrets

## 📝 Notes importantes

- Notifications : 4 types (validation compte, validation RDV, modification RDV, annulation RDV) — email uniquement
- Tout passage prioritaire exige un motif obligatoire, validé côté serveur
- Statut de file `Absent` prévu et géré
- Interconnexion SIH hors périmètre actuel
- `espace.blade.php` existe mais n'est pas utilisé dans les parcours actuels
- Sauvegarde/restauration : scripts `scripts/backup.sh` / `scripts/restore.sh` (à adapter sous Windows si utilisé hors WSL)

## 📂 Structure (principaux éléments)
- `app/Models/` : User, Service, Disponibilite, Salle, RendezVous, FileEntree, Parametre
- `app/Livewire/` : Auth, Patient, Secrétaire, Agent d'accueil, Médecin, Major, Admin
- `app/Services/` : CreneauService, PatientAccountService, ReservationService, FileAttenteService, StatisticsService, SalleService
- `app/Mail/` : emails transactionnels FR
- `resources/views/mail/` : templates emails (HTML + texte), layout partagé
- `database/seeders/` : RolePermissionSeeder, DemoSeeder
- `tests/Feature/` : tests par domaine (205 tests, 791 assertions)

## 📜 Licence
Projet pédagogique (PFE) — usage éducatif uniquement.

---

*Sunu Santé — « Dalal ak jàmm »*