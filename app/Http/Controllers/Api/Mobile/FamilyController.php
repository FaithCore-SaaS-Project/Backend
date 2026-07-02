<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\Member;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    /**
     * Return the authenticated user's family group and all its members.
     */
    public function myFamily(Request $request)
    {
        $user   = $request->user();
        $member = $user->member;

        if (!$member) {
            return response()->json([
                'success' => true,
                'data'    => null,
                'message' => 'No member profile linked to this account.',
            ]);
        }

        if (!$member->family_id) {
            return response()->json([
                'success' => true,
                'data'    => null,
                'message' => 'You have not set up a family group yet.',
            ]);
        }

        $family = Family::with('members')->findOrFail($member->family_id);

        return response()->json([
            'success' => true,
            'data'    => $family,
        ]);
    }

    /**
     * Add a new member to the user's family group.
     * Automatically creates a family if one does not exist yet.
     */
    public function addMember(Request $request)
    {
        $user   = $request->user();
        $member = $user->member;

        if (!$member) {
            return response()->json(['message' => 'Member profile not found. Please contact your church administrator.'], 404);
        }

        $validated = $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'relationship' => 'required|in:Spouse,Child,Parent,Sibling,Other',
            'gender'       => 'nullable|in:Male,Female,Other',
            'dob'          => 'nullable|date',
            'phone'        => 'nullable|string|max:30',
            'email'        => 'nullable|email|max:191',
        ]);

        // Auto-create a family group if none exists
        if (!$member->family_id) {
            $family = Family::create([
                'church_id'   => $user->church_id,
                'family_name' => $member->last_name . ' Family',
                'phone'       => $member->phone,
                'address'     => $member->address,
            ]);

            // Link the primary member to the new family
            $member->update(['family_id' => $family->id]);
        } else {
            $family = Family::findOrFail($member->family_id);
        }

        // Create the new member record under this family
        $newMember = Member::create([
            'church_id'   => $user->church_id,
            'family_id'   => $family->id,
            'first_name'  => $validated['first_name'],
            'last_name'   => $validated['last_name'],
            'gender'      => $validated['gender'] ?? null,
            'dob'         => $validated['dob'] ?? null,
            'phone'       => $validated['phone'] ?? null,
            'email'       => $validated['email'] ?? null,
            'status'      => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => $validated['first_name'] . ' has been added to your family.',
            'data'    => $family->load('members'),
        ], 201);
    }
}
