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
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'payment_method_id' => 'required|string'
        ]);

        $churchId = $request->user()->church_id;
        $plan = Plan::find($request->plan_id);

        // Fetch current active subscription
        $subscription = Subscription::where('church_id', $churchId)->latest()->first();

        // In a real application, you would interact with the gateway API here
        // For example, creating a Stripe subscription using the payment_method_id
        
        $subscription->update([
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'status' => 'active',
            // Update end_date logic based on gateway response
        ]);

        return response()->json([
            'message' => 'Subscription upgraded successfully',
            'subscription' => $subscription->load('plan')
        ]);
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request)
    {
        $churchId = $request->user()->church_id;
        $subscription = Subscription::where('church_id', $churchId)->latest()->first();

        if ($subscription && $subscription->status === 'active') {
            // Cancel at gateway here...
            
            $subscription->update([
                'status' => 'cancelled'
            ]);

            return response()->json([
                'message' => 'Subscription cancelled successfully. You will have access until the end of your billing cycle.'
            ]);
        }

        return response()->json(['message' => 'No active subscription found.'], 400);
    }
}
