<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/run-migrations', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate --force');
        \Illuminate\Support\Facades\Artisan::call('db:seed --class=PlanSeeder --force');
        \Illuminate\Support\Facades\Artisan::call('db:seed --class=RolePermissionSeeder --force');
        return "Migrations and Seeders ran successfully!";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});
