<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    private $systemRoles = ['Super Admin', 'Church Administrator', 'Pastor', 'Treasurer', 'Department Leader', 'Member'];

    public function index(Request $request)
    {
        $churchId = $request->user()->church_id;

        $roles = Role::with('permissions')
            ->whereIn('name', $this->systemRoles)
            ->orWhere('name', 'like', $churchId . '\_%')
            ->get()->map(function($role) use ($churchId) {
                
                $displayName = str_starts_with($role->name, $churchId . '_') 
                    ? substr($role->name, strlen($churchId . '_')) 
                    : $role->name;

                return [
                    'id' => $role->id,
                    'name' => $displayName,
                    'guard_name' => $role->guard_name,
                    'permissions' => $role->permissions->pluck('name'),
                    'users_count' => \App\Models\User::role($role->name)->where('church_id', $churchId)->count(),
                    'status' => 'Active',
                    'description' => $displayName . ' role permissions.',
                    'created_at' => $role->created_at
                ];
            });

        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        $churchId = $request->user()->church_id;
        $roleName = $churchId . '_' . $validated['name'];

        if (in_array($validated['name'], $this->systemRoles) || Role::where('name', $roleName)->exists()) {
            return response()->json(['message' => 'A role with this name already exists for your church.'], 422);
        }

        $role = Role::create([
            'name' => $roleName,
            'guard_name' => 'web'
        ]);

        if (!empty($validated['permissions'])) {
            $role->givePermissionTo($validated['permissions']);
        }

        return response()->json([
            'id' => $role->id,
            'name' => $validated['name'],
            'guard_name' => $role->guard_name,
            'permissions' => $role->permissions->pluck('name'),
            'users_count' => 0,
            'status' => 'Active',
            'description' => $validated['name'] . ' role permissions.',
            'created_at' => $role->created_at
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $role = Role::findOrFail($id);
        $churchId = $request->user()->church_id;

        if (!in_array($role->name, $this->systemRoles) && !str_starts_with($role->name, $churchId . '_')) {
             return response()->json(['error' => 'Unauthorized'], 403);
        }

        $roleData = $role->load('permissions')->toArray();
        $roleData['name'] = str_starts_with($role->name, $churchId . '_') ? substr($role->name, strlen($churchId . '_')) : $role->name;

        return response()->json($roleData);
    }

    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);
        $churchId = $request->user()->church_id;

        if (in_array($role->name, $this->systemRoles) || !str_starts_with($role->name, $churchId . '_')) {
            return response()->json(['error' => 'You cannot modify this role.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);
        
        $newRoleName = $churchId . '_' . $validated['name'];
        
        if ($newRoleName !== $role->name && Role::where('name', $newRoleName)->exists()) {
             return response()->json(['message' => 'A role with this name already exists.'], 422);
        }

        $role->update([
            'name' => $newRoleName
        ]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json([
            'id' => $role->id,
            'name' => $validated['name'],
            'guard_name' => $role->guard_name,
            'permissions' => $role->permissions->pluck('name'),
            'users_count' => \App\Models\User::role($role->name)->where('church_id', $churchId)->count(),
            'status' => 'Active',
            'description' => $validated['name'] . ' role permissions.',
            'created_at' => $role->created_at
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $role = Role::findOrFail($id);
        $churchId = $request->user()->church_id;

        if (in_array($role->name, $this->systemRoles)) {
            return response()->json(['error' => 'System roles cannot be deleted.'], 403);
        }

        if (!str_starts_with($role->name, $churchId . '_')) {
            return response()->json(['error' => 'You cannot delete this role.'], 403);
        }

        $role->delete();
        return response()->json(['success' => true]);
    }
}
