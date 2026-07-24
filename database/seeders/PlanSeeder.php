<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // Truncate existing plans
        DB::statement('PRAGMA foreign_keys = OFF;');
        Plan::truncate();
        DB::statement('PRAGMA foreign_keys = ON;');

        $plans = [
            [
                'name' => 'Free',
                'price' => 0.00,
                'member_limit' => 50,
                'free_sms_limit' => 0,
                'user_limit' => 2,
                'storage_limit_mb' => 500,
                'department_limit' => 3,
                'is_popular' => false,
                'badge' => null,
                'stripe_price_id' => null,
                'payhere_item_number' => null,
                'features' => json_encode([
                    'members' => ['export_csv' => false, 'bulk_import' => false, 'send_notification' => false],
                    'events' => ['registration' => false, 'attendance' => false],
                    'finance' => ['charts' => false, 'bank_accounts' => false, 'budgets' => false, 'e_receipts' => false],
                    'certificates' => ['enabled' => false, 'qr_verify' => false],
                    'letters' => ['enabled' => false],
                    'reports' => ['enabled' => false, 'export' => false],
                    'roles' => ['custom_roles' => false, 'permissions' => false],
                    'mobile' => ['giving' => false, 'event_registration' => false, 'push_notifications' => false, 'family' => false],
                    'settings' => ['finance' => false, 'notifications' => false, 'security' => false, 'system_maintenance' => false, 'integrations' => false, 'backup' => false, 'backup_schedule' => false, 'audit_logs' => false],
                    'support' => ['tickets' => false, 'phone' => false, 'priority' => false, 'account_manager' => false]
                ])
            ],
            [
                'name' => 'Basic',
                'price' => 3300.00,
                'member_limit' => 150,
                'free_sms_limit' => 300,
                'user_limit' => 5,
                'storage_limit_mb' => 2048,
                'department_limit' => 5,
                'is_popular' => false,
                'badge' => null,
                'stripe_price_id' => 'price_basic_monthly',
                'payhere_item_number' => 'plan_basic',
                'features' => json_encode([
                    'members' => ['export_csv' => true, 'bulk_import' => false, 'send_notification' => true],
                    'events' => ['registration' => true, 'attendance' => false],
                    'finance' => ['charts' => true, 'bank_accounts' => false, 'budgets' => false, 'e_receipts' => false],
                    'certificates' => ['enabled' => true, 'qr_verify' => false],
                    'letters' => ['enabled' => true],
                    'reports' => ['enabled' => false, 'export' => false],
                    'roles' => ['custom_roles' => false, 'permissions' => false],
                    'mobile' => ['giving' => true, 'event_registration' => true, 'push_notifications' => false, 'family' => false],
                    'settings' => ['finance' => true, 'notifications' => false, 'security' => false, 'system_maintenance' => false, 'integrations' => false, 'backup' => false, 'backup_schedule' => false, 'audit_logs' => false],
                    'support' => ['tickets' => true, 'phone' => false, 'priority' => false, 'account_manager' => false]
                ])
            ],
            [
                'name' => 'Standard',
                'price' => 6300.00,
                'member_limit' => 400,
                'free_sms_limit' => 800,
                'user_limit' => 10,
                'storage_limit_mb' => 5120,
                'department_limit' => 15,
                'is_popular' => true,
                'badge' => 'Most Popular',
                'stripe_price_id' => 'price_standard_monthly',
                'payhere_item_number' => 'plan_standard',
                'features' => json_encode([
                    'members' => ['export_csv' => true, 'bulk_import' => true, 'send_notification' => true],
                    'events' => ['registration' => true, 'attendance' => true],
                    'finance' => ['charts' => true, 'bank_accounts' => true, 'budgets' => true, 'e_receipts' => true],
                    'certificates' => ['enabled' => true, 'qr_verify' => true],
                    'letters' => ['enabled' => true],
                    'reports' => ['enabled' => true, 'export' => false],
                    'roles' => ['custom_roles' => true, 'permissions' => true],
                    'mobile' => ['giving' => true, 'event_registration' => true, 'push_notifications' => true, 'family' => true],
                    'settings' => ['finance' => true, 'notifications' => true, 'security' => true, 'system_maintenance' => true, 'integrations' => false, 'backup' => false, 'backup_schedule' => false, 'audit_logs' => false],
                    'support' => ['tickets' => true, 'phone' => true, 'priority' => false, 'account_manager' => false]
                ])
            ],
            [
                'name' => 'Premium',
                'price' => 11000.00,
                'member_limit' => 750,
                'free_sms_limit' => 1500,
                'user_limit' => 20,
                'storage_limit_mb' => 15360,
                'department_limit' => 30,
                'is_popular' => false,
                'badge' => null,
                'stripe_price_id' => 'price_premium_monthly',
                'payhere_item_number' => 'plan_premium',
                'features' => json_encode([
                    'members' => ['export_csv' => true, 'bulk_import' => true, 'send_notification' => true],
                    'events' => ['registration' => true, 'attendance' => true],
                    'finance' => ['charts' => true, 'bank_accounts' => true, 'budgets' => true, 'e_receipts' => true],
                    'certificates' => ['enabled' => true, 'qr_verify' => true],
                    'letters' => ['enabled' => true],
                    'reports' => ['enabled' => true, 'export' => true],
                    'roles' => ['custom_roles' => true, 'permissions' => true],
                    'mobile' => ['giving' => true, 'event_registration' => true, 'push_notifications' => true, 'family' => true],
                    'settings' => ['finance' => true, 'notifications' => true, 'security' => true, 'system_maintenance' => true, 'integrations' => true, 'backup' => true, 'backup_schedule' => false, 'audit_logs' => true],
                    'support' => ['tickets' => true, 'phone' => true, 'priority' => true, 'account_manager' => false]
                ])
            ],
            [
                'name' => 'Pro',
                'price' => 18500.00,
                'member_limit' => 1000,
                'free_sms_limit' => 2000,
                'user_limit' => 30,
                'storage_limit_mb' => 30720,
                'department_limit' => 50,
                'is_popular' => false,
                'badge' => null,
                'stripe_price_id' => 'price_pro_monthly',
                'payhere_item_number' => 'plan_pro',
                'features' => json_encode([
                    'members' => ['export_csv' => true, 'bulk_import' => true, 'send_notification' => true],
                    'events' => ['registration' => true, 'attendance' => true],
                    'finance' => ['charts' => true, 'bank_accounts' => true, 'budgets' => true, 'e_receipts' => true],
                    'certificates' => ['enabled' => true, 'qr_verify' => true],
                    'letters' => ['enabled' => true],
                    'reports' => ['enabled' => true, 'export' => true],
                    'roles' => ['custom_roles' => true, 'permissions' => true],
                    'mobile' => ['giving' => true, 'event_registration' => true, 'push_notifications' => true, 'family' => true],
                    'settings' => ['finance' => true, 'notifications' => true, 'security' => true, 'system_maintenance' => true, 'integrations' => true, 'backup' => true, 'backup_schedule' => true, 'audit_logs' => true],
                    'support' => ['tickets' => true, 'phone' => true, 'priority' => true, 'account_manager' => false]
                ])
            ],
            [
                'name' => 'Unlimited',
                'price' => 0.00, // Custom Pricing / contact support
                'member_limit' => 999999,
                'free_sms_limit' => 0,
                'user_limit' => 999999,
                'storage_limit_mb' => 999999,
                'department_limit' => 999999,
                'is_popular' => false,
                'badge' => null,
                'stripe_price_id' => 'price_unlimited_monthly',
                'payhere_item_number' => 'plan_unlimited',
                'features' => json_encode([
                    'members' => ['export_csv' => true, 'bulk_import' => true, 'send_notification' => true],
                    'events' => ['registration' => true, 'attendance' => true],
                    'finance' => ['charts' => true, 'bank_accounts' => true, 'budgets' => true, 'e_receipts' => true],
                    'certificates' => ['enabled' => true, 'qr_verify' => true],
                    'letters' => ['enabled' => true],
                    'reports' => ['enabled' => true, 'export' => true],
                    'roles' => ['custom_roles' => true, 'permissions' => true],
                    'mobile' => ['giving' => true, 'event_registration' => true, 'push_notifications' => true, 'family' => true],
                    'settings' => ['finance' => true, 'notifications' => true, 'security' => true, 'system_maintenance' => true, 'integrations' => true, 'backup' => true, 'backup_schedule' => true, 'audit_logs' => true],
                    'support' => ['tickets' => true, 'phone' => true, 'priority' => true, 'account_manager' => true]
                ])
            ]
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
