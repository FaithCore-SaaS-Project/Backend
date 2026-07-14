<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DepartmentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_departments', only: ['index', 'show']),
            new Middleware('permission:manage_departments', only: ['store', 'update', 'destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(
            Department::with(['leader', 'members'])->latest()->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $church = $request->user()->church;
        $plan = $church ? $church->activePlan() : null;
        if ($plan) {
            $deptCount = Department::count();
            if ($deptCount >= $plan->department_limit) {
                return response()->json([
                    'message' => 'Your plan (' . $plan->name . ') allows up to ' . $plan->department_limit . ' departments. Please upgrade your subscription.'
                ], 403);
            }
        }

        $validated = $request->validate([
            'department_name' => 'required|string|max:255',
            'leader_id' => 'nullable|exists:members,id',
            'description' => 'nullable|string'
        ]);

        $department = Department::create($validated);
        return response()->json($department->load(['leader', 'members']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department)
    {
        return response()->json($department->load(['leader', 'members']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'department_name' => 'sometimes|required|string|max:255',
            'leader_id' => 'nullable|exists:members,id',
            'description' => 'nullable|string'
        ]);

        $department->update($validated);
        return response()->json($department->load(['leader', 'members']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        $department->delete();
        return response()->json(['message' => 'Department deleted successfully']);
    }
}
