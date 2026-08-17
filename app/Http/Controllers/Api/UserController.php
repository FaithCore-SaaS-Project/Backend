<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private $systemRoles = ['Super Admin', 'Church Administrator', 'Pastor', 'Treasurer', 'Department Leader', 'Member'];

    public function index()
    {
        $churchId = auth()->user()->church_id;
        $users = User::where('church_id', $churchId)
            ->with('roles')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($user) use ($churchId) {
                $user->roles = $user->roles->map(function ($role) use ($churchId) {
                    $role->name = str_starts_with($role->name, $churchId . '_') ? substr($role->name, strlen($churchId . '_')) : $role->name;
                    return $role;
                });
                return $user;
            });

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $church = $request->user()->church;
        $churchId = $church->id;
        $plan = $church ? $church->activePlan() : null;
        if ($plan) {
            $userCount = User::where('church_id', $churchId)->count();
            if ($userCount >= $plan->user_limit) {
                return response()->json([
                    'message' => 'Your plan (' . $plan->name . ') allows up to ' . $plan->user_limit . ' admin users. Please upgrade your subscription.'
                ], 403);
            }
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->where(fn ($q) => $q->where('church_id', $churchId))
            ],
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'role' => ['required', 'string', Rule::notIn(['Super Admin'])],
            'status' => 'boolean'
        ]);

        $inputRole = $validated['role'];
        $actualRoleName = in_array($inputRole, $this->systemRoles) ? $inputRole : $churchId . '_' . $inputRole;

        if (!\Spatie\Permission\Models\Role::where('name', $actualRoleName)->exists()) {
             return response()->json(['message' => 'The selected role is invalid.'], 422);
        }

        $user = User::create([
            'church_id' => $churchId,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'] ?? true
        ]);

        $user->assignRole($actualRoleName);

        $user->load('roles');
        $user->roles = $user->roles->map(function ($role) use ($churchId) {
            $role->name = str_starts_with($role->name, $churchId . '_') ? substr($role->name, strlen($churchId . '_')) : $role->name;
            return $role;
        });

        return response()->json($user, 201);
    }

    public function show(string $id)
    {
        $user = User::findOrFail($id);
        $churchId = auth()->user()->church_id;
        if ($user->church_id !== $churchId) {
            abort(403);
        }
        
        $user->load('roles');
        $user->roles = $user->roles->map(function ($role) use ($churchId) {
            $role->name = str_starts_with($role->name, $churchId . '_') ? substr($role->name, strlen($churchId . '_')) : $role->name;
            return $role;
        });
        
        return response()->json($user);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $churchId = auth()->user()->church_id;
        
        if ($user->church_id !== $churchId) {
            abort(403);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id)->where(fn ($q) => $q->where('church_id', $churchId))
            ],
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'role' => ['required', 'string', Rule::notIn(['Super Admin'])],
            'status' => 'boolean'
        ]);

        $inputRole = $validated['role'];
        $actualRoleName = in_array($inputRole, $this->systemRoles) ? $inputRole : $churchId . '_' . $inputRole;

        if (!\Spatie\Permission\Models\Role::where('name', $actualRoleName)->exists()) {
             return response()->json(['message' => 'The selected role is invalid.'], 422);
        }

        $user->fill([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'] ?? true
        ]);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        $user->syncRoles([$actualRoleName]);

        $user->load('roles');
        $user->roles = $user->roles->map(function ($role) use ($churchId) {
            $role->name = str_starts_with($role->name, $churchId . '_') ? substr($role->name, strlen($churchId . '_')) : $role->name;
            return $role;
        });

        return response()->json($user);
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        if ($user->church_id !== auth()->user()->church_id) {
            abort(403);
        }
        
        if ($user->id === auth()->user()->id) {
            return response()->json(['error' => 'You cannot delete your own account.'], 403);
        }

        $user->delete();
        return response()->json(['success' => true]);
    }
}
