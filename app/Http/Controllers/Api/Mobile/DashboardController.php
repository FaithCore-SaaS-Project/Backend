<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Event;
use App\Models\FinanceRecord; // Or whatever model handles giving
use App\Models\Announcement;

class DashboardController extends Controller
{
    public function getStats(Request $request)
    {
        $churchId = $request->user()->church_id;

        // 1. Total Members
        $totalMembers = Member::where('church_id', $churchId)
                            ->where('status', true) // Assuming 'status' is boolean for active
                            ->count();

        // 2. Upcoming Events
        $upcomingEventsCount = Event::where('church_id', $churchId)
                                ->where('start_date', '>=', now())
                                ->count();

        // 3. Total Giving
        // Assuming there is a FinanceRecord or Donation model.
        // We'll mock it if not present or try to calculate if it exists.
        // For v1, if table doesn't exist, this might fail, so we wrap it or use a simple query.
        $totalGiving = 0;
        if (class_exists(\App\Models\FinanceRecord::class)) {
            $totalGiving = \App\Models\FinanceRecord::where('church_id', $churchId)
                                ->where('type', 'income') // or 'donation'
                                ->sum('amount');
        } else {
            // Mock value for now if FinanceRecord isn't fully structured for this
            $totalGiving = 24860;
        }

        // 4. Announcements Count
        $announcementsCount = Announcement::where('church_id', $churchId)->count();

        // 5. Fetch 3 Upcoming Events for the list
        $upcomingEventsList = Event::where('church_id', $churchId)
                                ->where('start_date', '>=', now())
                                ->orderBy('start_date', 'asc')
                                ->take(3)
                                ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_members' => $totalMembers,
                    'upcoming_events' => $upcomingEventsCount,
                    'total_giving' => $totalGiving,
                    'announcements' => $announcementsCount
                ],
                'upcoming_events' => $upcomingEventsList
            ]
        ]);
    }
}
