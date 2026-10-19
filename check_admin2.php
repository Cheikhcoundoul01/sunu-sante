<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$admin = User::where('email', 'admin.demo@sunu-sante.sn')->first();
if ($admin) {
    echo "Admin trouvé: " . $admin->email . " - Role: " . $admin->getRoleNames()->first() . " - is_demo: " . ($admin->is_demo ? 'true' : 'false') . " - statut: " . $admin->statut_compte . "\n";
} else {
    echo "Aucun admin trouvé avec cet email\n";
}