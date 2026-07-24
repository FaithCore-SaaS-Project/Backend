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
        
        // DUMMY IMPLEMENTATION: Directly add to balance for testing
        $church->topup_sms_balance += $request->amount;
        $church->save();

        return response()->json([
            'success' => true,
            'message' => "Successfully added {$request->amount} SMS credits to your top-up balance.",
            'new_balance' => $church->topup_sms_balance
        ]);
    }
}
