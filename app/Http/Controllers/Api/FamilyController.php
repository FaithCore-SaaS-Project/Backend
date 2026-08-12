<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Family;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

class FamilyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $church = $request->user()->church;
        
        $families = Family::where('church_id', $church->id)
            ->withCount('members')
            ->latest()
            ->get();
            
        // Map data for the frontend table
        $mapped = $families->map(function($f) {
            return [
                'id' => $f->id,
                'name' => $f->family_name,
                'members' => $f->members_count,
                'district' => $f->address ?? '',
                'joined' => $f->created_at->format('d M Y'),
                'status' => $f->status,
                'cellGroup' => $f->cell_group ?? '',
                'hasAddress' => !empty($f->address),
                'hasPhone' => !empty($f->phone)
            ];
        });
        
        return response()->json(['data' => $mapped]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'family_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
            'cell_group' => 'nullable|string|max:255',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:members,id'
        ]);

        $church = $request->user()->church;

        DB::transaction(function() use ($request, $church) {
            $family = Family::create([
                'church_id' => $church->id,
                'family_name' => $request->family_name,
                'phone' => $request->phone,
                'address' => $request->address,
                'status' => $request->status,
                'cell_group' => $request->cell_group,
            ]);

            if ($request->has('member_ids') && count($request->member_ids) > 0) {
                Member::whereIn('id', $request->member_ids)
                    ->where('church_id', $church->id)
                    ->update(['family_id' => $family->id]);
            }
        });

        return response()->json(['message' => 'Family created successfully']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $family = Family::with('members')->findOrFail($id);
        
        return response()->json([
            'data' => [
                'id' => $family->id,
                'name' => $family->family_name,
                'members' => $family->members->count(),
                'district' => $family->address ?? '',
                'joined' => $family->created_at->format('d M Y'),
                'status' => $family->status,
                'cellGroup' => $family->cell_group ?? '',
                'household_members' => $family->members->map(function($m) {
                    return [
                        'id' => $m->id,
                        'name' => trim($m->first_name . ' ' . $m->last_name),
                    ];
                })
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'family_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
            'cell_group' => 'nullable|string|max:255',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'exists:members,id'
        ]);

        $family = Family::findOrFail($id);

        DB::transaction(function() use ($request, $family) {
            $family->update([
                'family_name' => $request->family_name,
                'phone' => $request->phone,
                'address' => $request->address,
                'status' => $request->status,
                'cell_group' => $request->cell_group,
            ]);

            if ($request->has('member_ids')) {
                // Remove old members not in the new list
                Member::where('family_id', $family->id)
                    ->whereNotIn('id', $request->member_ids)
                    ->update(['family_id' => null]);
                    
                // Add new members
                if (count($request->member_ids) > 0) {
                    Member::whereIn('id', $request->member_ids)
                        ->update(['family_id' => $family->id]);
                }
            }
        });

        return response()->json(['message' => 'Family updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $family = Family::findOrFail($id);
        
        DB::transaction(function() use ($family) {
            // Nullify members before deleting
            Member::where('family_id', $family->id)->update(['family_id' => null]);
            $family->delete();
        });
        
        return response()->json(['message' => 'Family deleted successfully']);
    }
}
