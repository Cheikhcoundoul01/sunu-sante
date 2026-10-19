<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel::class')->bootstrap();

use App\Models\User;

$admin = User::where('email', 'admin.demo@sunu-sante.sn')->first();
if ($admin) {
    echo 'Admin trouvé: ' . $admin->email . ' - Role: ' . $admin->getRoleNames()->first() . ' - is_demo: ' . ($admin->is_demo ? 'true' : 'false') . ' - statut: ' . $admin->statut_compte;
} else {
    echo 'Aucun admin trouvé avec cet email';
}