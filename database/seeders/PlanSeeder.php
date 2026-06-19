<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'price' => 0.00,
                'member_limit' => 100,
                'user_limit' => 3,
                'storage_limit' => 1 // GB
            ],
            [
                'name' => 'Basic',
                'price' => 29.99,
                'member_limit' => 500,
                'user_limit' => 5,
                'storage_limit' => 5 // GB
            ],
            [
                'name' => 'Standard',
                'price' => 69.99,
                'member_limit' => 2000,
                'user_limit' => 10,
                'storage_limit' => 10 // GB
            ],
            [
                'name' => 'Premium',
                'price' => 119.99,
                'member_limit' => 999999, // Unlimited
                'user_limit' => 25,
                'storage_limit' => 100 // GB
            ],
            [
                'name' => 'Enterprise',
                'price' => 299.99,
                'member_limit' => 999999, // Unlimited
                'user_limit' => 999999, // Unlimited
                'storage_limit' => 1000 // GB
            ]
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
