<?php

namespace App\Imports;

use App\Models\Member;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Auth;

class MembersImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $user = Auth::user();
        if (!$user) return null;

        $lastMember = Member::orderBy('id', 'desc')->first();
        $nextNumber = $lastMember ? ((int) filter_var($lastMember->member_no, FILTER_SANITIZE_NUMBER_INT)) + 1 : 1001;
        $memberNo   = 'MBR-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        return new Member([
            'church_id'  => $user->church_id,
            'member_no'  => $memberNo,
            'first_name' => $row['first_name'] ?? $row['firstname'] ?? 'Unknown',
            'last_name'  => $row['last_name'] ?? $row['lastname'] ?? 'Unknown',
            'email'      => $row['email'] ?? null,
            'phone'      => $row['phone'] ?? null,
            'gender'     => strtolower($row['gender'] ?? 'other'),
            'dob'        => $row['dob'] ?? null,
            'status'     => 'active',
            'membership_date' => now()->toDateString(),
        ]);
    }
}
