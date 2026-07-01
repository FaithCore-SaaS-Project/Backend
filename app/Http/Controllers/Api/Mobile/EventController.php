<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type', 'upcoming'); // 'upcoming', 'registered', 'past'
        $query = Event::where('church_id', $request->user()->church_id);

        if ($type === 'upcoming') {
            $query->where('start_date', '>=', now()->toDateString());
        } elseif ($type === 'past') {
            $query->where('start_date', '<', now()->toDateString());
        } elseif ($type === 'registered') {
            $memberId = $request->user()->member->id ?? 0;
            $query->whereHas('registrations', function($q) use ($memberId) {
                $q->where('member_id', $memberId);
            });
        }

        $events = $query->orderBy('start_date', 'asc')->paginate(15);
        return response()->json($events);
    }

    public function show(string $id)
    {
        $event = Event::where('church_id', request()->user()->church_id)->findOrFail($id);
        return response()->json($event);
    }
}
