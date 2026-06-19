<?php
echo "Scaffolding Phase 9 models and controllers...\n";
echo shell_exec("php artisan make:model EventRegistration");
echo shell_exec("php artisan make:model DepartmentMember");
echo shell_exec("php artisan make:controller Api/ReportController --api");

// Add PlanSeeder to DatabaseSeeder
$dbSeederPath = 'database/seeders/DatabaseSeeder.php';
$content = file_get_contents($dbSeederPath);
if (strpos($content, 'PlanSeeder::class') === false) {
    $content = str_replace('// User::factory(10)->create();', '$this->call([PlanSeeder::class]);', $content);
    file_put_contents($dbSeederPath, $content);
    echo "Added PlanSeeder to DatabaseSeeder.\n";
}

echo "Scaffolding Complete!\n";
