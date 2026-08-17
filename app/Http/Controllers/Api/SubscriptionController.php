<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Plan;

class SubscriptionController extends Controller
{
    /**
     * Display current subscription and available plans.
     */
    public function index(Request $request)
    {
        $churchId = $request->user()->church_id;
        $currentSubscription = Subscription::with('plan')->where('church_id', $churchId)->latest()->first();
        $plans = Plan::all();

        return response()->json([
            'current_subscription' => $currentSubscription,
            'available_plans' => $plans
        ]);
    }

    /**
     * Upgrade subscription plan.
     */
    public function upgrade(Request $request)
    {
        return response()->json([
            'error' => 'Please use the official checkout endpoints (/api/checkout/...) to upgrade your subscription.'
        ], 400);
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request)
    {
        return response()->json([
            'error' => 'Please manage subscription cancellations directly through your payment provider portal (Stripe/PayPal) to ensure recurring billing is halted.'
        ], 400);
    }
}
