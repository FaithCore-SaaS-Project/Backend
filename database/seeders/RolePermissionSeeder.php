<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view_members', 'create_members', 'edit_members', 'delete_members',
            'view_finance', 'create_expenses', 'create_income',
            'view_documents', 'manage_settings',
            'view_events', 'create_events', 'edit_events', 'delete_events',
            'view_reports', 'manage_users', 'manage_subscription'
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign created permissions
        $superAdmin = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin']);
        // Super Admin gets all permissions via a Gate in AuthServiceProvider normally, but we can assign all here
        $superAdmin->givePermissionTo(\Spatie\Permission\Models\Permission::all());

        $churchAdmin = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Church Administrator']);
        $churchAdmin->givePermissionTo([
            'view_members', 'create_members', 'edit_members', 'delete_members',
            'view_finance', 'create_expenses', 'create_income',
            'view_documents', 'manage_settings',
            'view_events', 'create_events', 'edit_events', 'delete_events',
            'view_reports', 'manage_users', 'manage_subscription'
        ]);

        $pastor = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Pastor']);
        $pastor->givePermissionTo([
            'view_members', 'view_events', 'view_reports', 'view_documents'
        ]);

        $treasurer = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Treasurer']);
        $treasurer->givePermissionTo([
            'view_finance', 'create_expenses', 'create_income', 'view_reports'
        ]);

        $deptLeader = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Department Leader']);
        $deptLeader->givePermissionTo([
            'view_members', 'view_events', 'view_reports'
        ]);

        $member = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Member']);
        $member->givePermissionTo([
            'view_events'
        ]);
    }
}
