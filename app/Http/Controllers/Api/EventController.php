<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class EventController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view_events', only: ['index', 'show']),
            new Middleware('permission:create_events', only: ['store', 'register']),
            new Middleware('permission:edit_events', only: ['update']),
            new Middleware('permission:delete_events', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $events = Event::latest('event_date')->get();
        return response()->json($events->map(fn($e) => $this->formatEvent($e)));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'type' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'attendees' => 'sometimes|integer|min:0',
            'maxCapacity' => 'required|integer|min:1',
            'status' => 'required|string|max:255',
            'organizer' => 'required|string|max:255',
            'description' => 'nullable|string',
            'createdOn' => 'nullable|date',
        ]);

        $dbData = [
            'event_name' => $validated['name'],
            'subtitle' => $validated['subtitle'] ?? null,
            'type' => $validated['type'],
            'event_date' => $validated['date'],
            'event_time' => $validated['time'],
            'venue' => $validated['location'],
            'attendees' => $validated['attendees'] ?? 0,
            'max_capacity' => $validated['maxCapacity'],
            'status' => $validated['status'],
            'organizer' => $validated['organizer'],
            'description' => $validated['description'] ?? null,
            'created_on' => $validated['createdOn'] ?? date('Y-m-d'),
        ];

        $event = Event::create($dbData);

        return response()->json($this->formatEvent($event), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $event = Event::findOrFail($id);
        return response()->json($this->formatEvent($event));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $event = Event::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'type' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'attendees' => 'sometimes|integer|min:0',
            'maxCapacity' => 'required|integer|min:1',
            'status' => 'required|string|max:255',
            'organizer' => 'required|string|max:255',
            'description' => 'nullable|string',
            'createdOn' => 'nullable|date',
        ]);

        $dbData = [
            'event_name' => $validated['name'],
            'subtitle' => $validated['subtitle'] ?? null,
            'type' => $validated['type'],
            'event_date' => $validated['date'],
            'event_time' => $validated['time'],
            'venue' => $validated['location'],
            'attendees' => $validated['attendees'] ?? $event->attendees,
            'max_capacity' => $validated['maxCapacity'],
            'status' => $validated['status'],
            'organizer' => $validated['organizer'],
            'description' => $validated['description'] ?? null,
            'created_on' => $validated['createdOn'] ?? $event->created_on,
        ];

        $event->update($dbData);

        return response()->json($this->formatEvent($event));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $event = Event::findOrFail($id);
        $event->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Register a member for an event.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'member_id' => 'required|exists:members,id',
            'status' => 'sometimes|string|in:registered,checked_in,no_show',
        ]);

        $registration = EventRegistration::updateOrCreate(
            [
                'event_id' => $validated['event_id'],
                'member_id' => $validated['member_id']
            ],
            [
                'status' => $validated['status'] ?? 'registered'
            ]
        );

        // Recalculate event attendees
        $event = Event::findOrFail($validated['event_id']);
        $event->update([
            'attendees' => $event->members()->count()
        ]);

        return response()->json([
            'success' => true,
            'registration' => $registration,
            'attendees' => $event->attendees
        ]);
    }

    /**
     * Helper to format Event object to match frontend keys.
     */
    private function formatEvent($event)
    {
        return [
            'id' => (string) $event->id,
            'name' => $event->event_name,
            'subtitle' => $event->subtitle,
            'type' => $event->type,
            'date' => $event->event_date instanceof \DateTimeInterface 
                ? $event->event_date->format('Y-m-d') 
                : substr($event->event_date, 0, 10),
            'time' => $event->event_time,
            'location' => $event->venue,
            'attendees' => (int) $event->attendees,
            'maxCapacity' => (int) $event->max_capacity,
            'status' => $event->status,
            'organizer' => $event->organizer,
            'description' => $event->description,
            'tenantId' => (string) $event->church_id,
            'createdOn' => $event->created_on 
                ? ($event->created_on instanceof \DateTimeInterface ? $event->created_on->format('Y-m-d') : substr($event->created_on, 0, 10)) 
                : ($event->created_at ? $event->created_at->format('Y-m-d') : date('Y-m-d')),
        ];
    }
}
