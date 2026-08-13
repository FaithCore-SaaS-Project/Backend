<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::with('church')->first();
echo "Church ID: " . $user->church->registration_no . "\n";
echo "Email: " . $user->email . "\n";
