<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Event;
use App\Models\FinanceIncome;
use App\Models\Announcement;

class DashboardController extends Controller
{
    public function getStats(Request $request)
    {
        $churchId = $request->user()->church_id;

        // 1. Total Members (active)
        $totalMembers = Member::where('church_id', $churchId)
                            ->where('status', 'active')
                            ->count();

        // 2. Upcoming Events count
        $upcomingEventsCount = Event::where('church_id', $churchId)
                                ->where('event_date', '>=', now()->toDateString())
                                ->count();

        // 3. Total Giving this month
        $totalGiving = FinanceIncome::where('church_id', $churchId)
                            ->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year)
                            ->sum('amount');

        // 4. Announcements Count (published, last 30 days)
        $announcementsCount = Announcement::where('church_id', $churchId)
                                ->where('is_published', true)
                                ->where('created_at', '>=', now()->subDays(30))
                                ->count();

        // 5. Fetch 3 Upcoming Events for the home list
        $upcomingEventsList = Event::where('church_id', $churchId)
                                ->where('event_date', '>=', now()->toDateString())
                                ->orderBy('event_date', 'asc')
                                ->take(3)
                                ->get([
                                    'id', 'event_name', 'description',
                                    'event_date', 'event_time', 'venue',
                                    'type', 'status'
                                ]);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_members'   => $totalMembers,
                    'upcoming_events' => $upcomingEventsCount,
                    'total_giving'    => $totalGiving,
                    'announcements'   => $announcementsCount,
                ],
                'upcoming_events' => $upcomingEventsList,
            ]
        ]);
    }
}
