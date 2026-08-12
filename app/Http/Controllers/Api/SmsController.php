<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    /**
     * Get SMS Dashboard Data
     */
    public function getDashboard(Request $request)
    {
        $church = $request->user()->church;
        
        $activePlan = $church->activePlan();
        $freeLimit = $activePlan ? $activePlan->free_sms_limit : 0;

        return response()->json([
            'monthly_limit' => $freeLimit,
            'monthly_used' => $church->monthly_sms_used,
            'topup_balance' => $church->topup_sms_balance,
            'sender_id' => $church->sms_sender_id ?: config('services.smslenz.sender_id', 'FAITHCORE')
        ]);
    }

    /**
     * Send SMS or Bulk SMS
     */
    public function sendSms(Request $request, \App\Services\SmsService $smsService)
    {
        $request->validate([
            'message' => 'required|string|max:1500',
            'contacts' => 'required|array',
            'contacts.*' => 'string'
        ]);

        $church = $request->user()->church;
        $contacts = $request->contacts;

        try {
            if (count($contacts) === 1) {
                $result = $smsService->sendSms($church, $contacts[0], $request->message);
            } else {
                $result = $smsService->sendBulkSms($church, $contacts, $request->message);
            }

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Dummy endpoint for purchasing SMS Top-up
     * In a real app, this would create a Stripe/PayHere checkout session.
     */
    public function buyTopup(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:100' // e.g. buying 500 SMS
        ]);

        $church = $request->user()->church;
        
        // In this Church Management System, SMS top-ups are manually requested and approved by Super Admins.
        $price = $request->amount; // Simplified: 1 SMS = 1 Rs. In reality, you'd map package prices.

        \App\Models\SmsTopupRequest::create([
            'church_id' => $church->id,
            'amount' => $request->amount,
            'price' => $price,
            'status' => 'Pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => "Successfully submitted a request for {$request->amount} SMS credits. Waiting for Admin approval.",
        ]);
    }

    /**
     * Admin: Get all Top-up Requests
     */
    public function adminGetRequests(Request $request)
    {
        // Add auth check for superadmin here if needed
        $requests = \App\Models\SmsTopupRequest::with('church')->latest()->get();
        return response()->json($requests);
    }

    /**
     * Admin: Approve a Top-up Request
     */
    public function adminApproveRequest(Request $request, $id)
    {
        $topupRequest = \App\Models\SmsTopupRequest::findOrFail($id);
        
        if ($topupRequest->status !== 'Pending') {
            return response()->json(['message' => 'Request already processed'], 400);
        }

        $topupRequest->status = 'Approved';
        $topupRequest->save();

        // Add credits to the church
        $church = $topupRequest->church;
        $church->topup_sms_balance += $topupRequest->amount;
        $church->save();

        return response()->json(['message' => 'Successfully approved request and added credits to the church']);
}
