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
        $church = Church::with('services')->find($churchId);

        if (!$church) {
            return response()->json(['success' => false, 'message' => 'Church not found'], 404);
        }

        // Calculate stats
        $membersCount = Member::where('church_id', $churchId)->count();
        $ministriesCount = Department::where('church_id', $churchId)->count();
        
        $settings = $church->visibility_settings ?? [];
        $showPhone = $settings['show_phone'] ?? true;
        $showEmail = $settings['show_email'] ?? true;
        $showAddress = $settings['show_address'] ?? true;

        $services = $church->services->map(function($service) {
            return [
                'name' => $service->name,
                'time' => ($service->start_time ? $service->start_time : '') . ($service->end_time ? ' - ' . $service->end_time : '')
            ];
        });

        // Use real data, falling back gracefully
        $data = [
            'name' => $church->church_name,
            'address' => $showAddress ? $church->address : null,
            'phone' => $showPhone ? $church->phone : null,
            'email' => $showEmail ? $church->email : null,
            'logo' => $church->logo_url ?? null,
            'cover_image' => $church->cover_image ?? null, // Will use custom_splash in frontend
            'about' => $church->about,
            'website' => $church->website,
            'facebook' => $church->facebook,
            'instagram' => $church->instagram,
            'youtube' => $church->youtube,
            'twitter' => $church->twitter,
            'stats' => [
                'members' => number_format($membersCount) . '+',
                'ministries' => (string) $ministriesCount,
                'established' => 'Since ' . ($church->year_established ?? date('Y')),
                'pastor' => $church->pastor_name ?? 'Senior Pastor'
            ],
            'services' => count($services) > 0 ? $services : [] // Empty array handled by frontend
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
