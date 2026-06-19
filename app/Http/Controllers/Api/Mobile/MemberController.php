<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index()
    {
        return Member::paginate(20);
    }

    public function store(StoreMemberRequest $request)
    {
        $member = Member::create([
            ...$request->validated(),
            'church_id' => auth()->user()->church_id,
            'member_no' => 'MEM-' . time(), // basic generator, can be refined
        ]);

        return response()->json($member, 201);
    }

    public function show($id)
    {
        return Member::findOrFail($id);
    }

    public function update(UpdateMemberRequest $request, $id)
    {
        $member = Member::findOrFail($id);
        $member->update($request->validated());
        return response()->json($member);
    }

    public function destroy($id)
    {
        Member::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
