<?php

echo "Creating Models and Migrations...\n";
echo shell_exec("php artisan make:model Invoice -m");
echo shell_exec("php artisan make:model Payment");
echo shell_exec("php artisan make:model PaymentLog");

echo "Creating Controllers...\n";
echo shell_exec("php artisan make:controller Api/CheckoutController --api");
echo shell_exec("php artisan make:controller Api/WebhookController");

echo "Creating Middleware...\n";
echo shell_exec("php artisan make:middleware SubscriptionMiddleware");

echo "Creating Seeder...\n";
echo shell_exec("php artisan make:seeder PlanSeeder");

echo "\nPhase 8 Scaffolding Complete!\n";
