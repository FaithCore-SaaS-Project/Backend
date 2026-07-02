<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type', 'upcoming'); // 'upcoming', 'registered', 'past'
        $query = Event::where('church_id', $request->user()->church_id);

        if ($type === 'upcoming') {
            $query->where('event_date', '>=', now()->toDateString());
        } elseif ($type === 'past') {
            $query->where('event_date', '<', now()->toDateString());
        } elseif ($type === 'registered') {
            $memberId = $request->user()->member->id ?? 0;
            $query->whereHas('members', function($q) use ($memberId) {
                $q->where('member_id', $memberId);
            });
        }

        $events = $query->orderBy('event_date', 'asc')->paginate(15);
        return response()->json($events);
    }

    public function show(string $id)
    {
        $event = Event::where('church_id', request()->user()->church_id)->findOrFail($id);
        return response()->json($event);
    }

    public function register(Request $request, string $id)
    {
        $user = $request->user();
        $member = $user->member;

        if (!$member) {
            return response()->json(['message' => 'Member profile not found. Please contact your church administrator.'], 404);
        }

        $event = Event::where('church_id', $user->church_id)->findOrFail($id);

        // Check if already registered
        $alreadyRegistered = EventRegistration::where('event_id', $event->id)
            ->where('member_id', $member->id)
            ->exists();

        if ($alreadyRegistered) {
            return response()->json(['message' => 'You are already registered for this event.'], 409);
        }

        // Check capacity
        if ($event->max_capacity && $event->attendees >= $event->max_capacity) {
            return response()->json(['message' => 'This event is fully booked.'], 422);
        }

        EventRegistration::create([
            'church_id' => $user->church_id,
            'event_id'  => $event->id,
            'member_id' => $member->id,
        ]);

        // Increment attendees count
        $event->increment('attendees');

        return response()->json([
            'success' => true,
            'message' => 'You have successfully registered for this event!'
        ]);
    }
}
