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
        $noPhoneNumbers = [];
        $smsSent = 0;

        // Process SMS manually through SmsService if requested
        if (in_array('sms', $validated['channels'])) {
            $validPhoneMembers = $members->filter(fn($m) => !empty($m->phone));
            $noPhoneNumbers = $members->filter(fn($m) => empty($m->phone))
                                      ->map(fn($m) => trim($m->first_name . ' ' . $m->last_name))
                                      ->values()
                                      ->toArray();

            $phoneNumbers = $validPhoneMembers->pluck('phone')->toArray();
            
            if (count($phoneNumbers) > 0) {
                try {
                    $church = $request->user()->church;
                    app(\App\Services\SmsService::class)->sendBulkSms($church, $phoneNumbers, $validated['message']);
                    $smsSent = count($phoneNumbers);
                } catch (\Exception $e) {
                    return response()->json([
                        'message' => 'Failed to send SMS: ' . $e->getMessage()
                    ], 400);
                }
            }

            // Remove 'sms' channel before passing to standard Notification facade
            $validated['channels'] = array_values(array_filter($validated['channels'], fn($c) => $c !== 'sms'));
        }

        // Send other channels (database, mail) if any remain
        if (count($validated['channels']) > 0) {
            Notification::send($members, new MemberAnnouncement(
                $validated['subject'],
                $validated['message'],
                $validated['channels']
            ));
        }

        return response()->json([
            'message' => 'Notifications sent successfully.',
            'total_members' => $members->count(),
            'sms_sent' => $smsSent,
            'no_phone_numbers' => $noPhoneNumbers
        ]);
    }
}
