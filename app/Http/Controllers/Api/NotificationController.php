<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
use App\Notifications\MemberAnnouncement;
use Illuminate\Support\Facades\Notification;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return response()->json([
            'unread' => $request->user()->unreadNotifications,
            'all' => $request->user()->notifications()->take(50)->get()
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
            return response()->json(['success' => true]);
        }
        return response()->json(['message' => 'Notification not found'], 404);
    }

    /**
     * Send a notification to members.
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'channels' => 'required|array',
            'member_ids' => 'required|array',
            'member_ids.*' => 'exists:members,id'
        ]);

        $members = Member::whereIn('id', $validated['member_ids'])->get();

        // In a real app, you might want to use Notification::send() with a queue.
        Notification::send($members, new MemberAnnouncement(
            $validated['subject'],
            $validated['message'],
            $validated['channels']
        ));

        return response()->json(['message' => 'Notifications sent successfully to ' . $members->count() . ' members.']);
    }
}
