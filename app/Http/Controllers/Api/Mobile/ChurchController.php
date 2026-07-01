<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Church;
use App\Models\Member;
use App\Models\Department; // Assuming ministries are departments

class ChurchController extends Controller
{
    public function details(Request $request)
    {
        $churchId = $request->user()->church_id;
        $church = Church::find($churchId);

        if (!$church) {
            return response()->json(['success' => false, 'message' => 'Church not found'], 404);
        }

        // Mock or calculate stats
        $membersCount = Member::where('church_id', $churchId)->count();
        $ministriesCount = Department::where('church_id', $churchId)->count();
        if ($ministriesCount === 0) $ministriesCount = 12; // fallback mock
        
        $data = [
            'name' => $church->church_name,
            'address' => $church->address,
            'logo' => $church->logo_url ?? null,
            'cover_image' => 'https://images.unsplash.com/photo-1438283173091-5dbf5c5a3206?auto=format&fit=crop&q=80&w=800',
            'about' => $church->about ?? "Leading people to love God, love others and serve the world. We welcome everyone to grow in faith.",
            'stats' => [
                'members' => number_format($membersCount) . '+',
                'ministries' => (string) $ministriesCount,
                'established' => 'Since ' . ($church->established_year ?? '2010'),
                'pastor' => $church->pastor_name ?? 'Senior Pastor'
            ],
            'services' => [
                ['name' => 'Sunday Worship Service', 'time' => '9:00 AM - 10:30 AM'],
                ['name' => 'Sunday School', 'time' => '10:45 AM - 11:30 AM'],
                ['name' => 'Youth Service', 'time' => '5:00 PM - 6:30 PM'],
                ['name' => 'Prayer Meeting', 'time' => 'Wednesday, 7:00 PM']
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
