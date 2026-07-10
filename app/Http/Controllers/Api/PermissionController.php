<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $permissions = Permission::all()->map(function($perm) {
            // Count roles that have this permission
            $rolesCount = Role::permission($perm->name)->count();
            
            // Map permission to modules based on naming conventions
            $module = 'Others';
            if (str_contains($perm->name, 'member')) $module = 'Members';
            elseif (str_contains($perm->name, 'finance') || str_contains($perm->name, 'expense') || str_contains($perm->name, 'income') || str_contains($perm->name, 'budget') || str_contains($perm->name, 'bank')) $module = 'Finance';
            elseif (str_contains($perm->name, 'event')) $module = 'Events';
            elseif (str_contains($perm->name, 'report')) $module = 'Reports';
            elseif (str_contains($perm->name, 'document')) $module = 'Documents';
            elseif (str_contains($perm->name, 'setting')) $module = 'Settings';
            elseif (str_contains($perm->name, 'user') || str_contains($perm->name, 'permission') || str_contains($perm->name, 'role')) $module = 'Users & Roles';

            return [
                'id' => $perm->id,
                'name' => ucwords(str_replace('_', ' ', $perm->name)),
                'key' => $perm->name,
                'module' => $module,
                'type' => 'System',
                'roles' => $rolesCount,
                'status' => 'Active',
                'description' => 'Grants privilege to ' . str_replace('_', ' ', $perm->name)
            ];
        });

        return response()->json($permissions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name|max:255',
        ]);

        $slug = strtolower(str_replace(' ', '_', $validated['name']));

        $perm = Permission::create([
            'name' => $slug,
            'guard_name' => 'web'
        ]);

        return response()->json([
            'id' => $perm->id,
            'name' => ucwords(str_replace('_', ' ', $perm->name)),
            'key' => $perm->name,
            'module' => 'Others',
            'type' => 'Custom',
            'roles' => 0,
            'status' => 'Active',
            'description' => 'Grants privilege to ' . str_replace('_', ' ', $perm->name)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $permission = Permission::findOrFail($id);
        return response()->json($permission);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
        ]);

        $slug = strtolower(str_replace(' ', '_', $validated['name']));

        $permission->update([
            'name' => $slug
        ]);

        return response()->json([
            'id' => $permission->id,
            'name' => ucwords(str_replace('_', ' ', $permission->name)),
            'key' => $permission->name,
            'module' => 'Others',
            'type' => 'Custom',
            'roles' => Role::permission($permission->name)->count(),
            'status' => 'Active',
            'description' => 'Grants privilege to ' . str_replace('_', ' ', $permission->name)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $permission = Permission::findOrFail($id);
        
        // Prevent deleting core system permissions
        $systemPerms = [
            'view_members', 'create_members', 'edit_members', 'delete_members',
            'view_finance', 'create_expenses', 'create_income',
            'view_documents', 'manage_settings',
            'view_events', 'create_events', 'edit_events', 'delete_events',
            'view_reports', 'manage_users', 'manage_subscription'
        ];
        if (in_array($permission->name, $systemPerms)) {
            return response()->json(['error' => 'System permissions cannot be deleted.'], 403);
        }

        $permission->delete();
        return response()->json(['success' => true]);
    }
}
