<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::query();

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', "%{$searchTerm}%")
                  ->orWhere('last_name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('phone', 'like', "%{$searchTerm}%")
                  ->orWhere('member_no', 'like', "%{$searchTerm}%");
            });
        }

        return $query->paginate(20);
    }

    public function store(StoreMemberRequest $request)
    {
        $data = array_merge($request->validated(), [
            'church_id' => auth()->user()->church_id,
            'member_no' => 'MEM-' . time(),
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

        return response()->json($member, 201);
    }

    public function show($id)
    {
        return Member::findOrFail($id);
    }

    public function update(UpdateMemberRequest $request, $id)
    {
        $member = Member::findOrFail($id);
        $data = $request->validated();

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
        return response()->json($member);
    }

    public function destroy($id)
    {
        Member::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
