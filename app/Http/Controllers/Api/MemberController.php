<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Models\Member;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class MemberController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_members', only: ['index', 'show']),
            new Middleware('permission:create_members', only: ['store']),
            new Middleware('permission:edit_members', only: ['update']),
            new Middleware('permission:delete_members', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // ChurchScope automatically filters by church_id
        return MemberResource::collection(
            Member::with('family')->paginate(50)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMemberRequest $request)
    {
        $church = $request->user()->church;
        $plan = $church ? $church->activePlan() : null;
        if ($plan) {
            $memberCount = Member::count();
            if ($memberCount >= $plan->member_limit) {
                return response()->json([
                    'message' => 'Your plan (' . $plan->name . ') allows up to ' . $plan->member_limit . ' members. Please upgrade your subscription.'
                ], 403);
            }
        }

        // Auto-generate a unique member number for this church
        $lastMember = Member::orderBy('id', 'desc')->first();
        $nextNumber = $lastMember ? ((int) filter_var($lastMember->member_no, FILTER_SANITIZE_NUMBER_INT)) + 1 : 1001;
        $memberNo   = 'MBR-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        $data = array_merge($request->validated(), [
            'member_no' => $memberNo,
            'status'    => $request->input('status', 'active'),
            'dob'       => $request->input('dob'),
            'address'   => $request->input('address'),
            'occupation'=> $request->input('occupation'),
            'membership_date' => $request->input('membership_date', now()->toDateString()),
            'family_id' => $request->input('family_id'),
        ]);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('photos', 'public');
        }
        if ($request->hasFile('baptism_certificate')) {
            $data['baptism_certificate'] = $request->file('baptism_certificate')->store('certificates', 'public');
        }
        if ($request->hasFile('marriage_certificate')) {
            $data['marriage_certificate'] = $request->file('marriage_certificate')->store('certificates', 'public');
        }
        if ($request->hasFile('birth_certificate')) {
            $data['birth_certificate'] = $request->file('birth_certificate')->store('certificates', 'public');
        }

        $member = Member::create($data);

        return new MemberResource($member);
    }

    /**
     * Display the specified resource.
     */
    public function show(Member $member)
    {
        return new MemberResource($member->load('family'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMemberRequest $request, Member $member)
    {
        $data = array_merge($request->validated(), [
            'dob'       => $request->input('dob', $member->dob),
            'address'   => $request->input('address', $member->address),
            'occupation'=> $request->input('occupation', $member->occupation),
            'status'    => $request->input('status', $member->status),
        ]);
        
        if ($request->has('family_id')) {
            $data['family_id'] = $request->input('family_id');
        }

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('photos', 'public');
        }
        if ($request->hasFile('baptism_certificate')) {
            $data['baptism_certificate'] = $request->file('baptism_certificate')->store('certificates', 'public');
        }
        if ($request->hasFile('marriage_certificate')) {
            $data['marriage_certificate'] = $request->file('marriage_certificate')->store('certificates', 'public');
        }
        if ($request->hasFile('birth_certificate')) {
            $data['birth_certificate'] = $request->file('birth_certificate')->store('certificates', 'public');
        }

        $member->update($data);
        return new MemberResource($member->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\Member $member)
    {
        $member->delete();
        return response()->json(['message' => 'Member deleted successfully.']);
    }

    /**
     * Import members from Excel/CSV file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls|max:10240'
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\MembersImport, $request->file('file'));
            return response()->json(['message' => 'Members imported successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to import members', 'error' => $e->getMessage()], 500);
        }
    }
}
