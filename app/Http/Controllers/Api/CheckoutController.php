<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use App\Models\Subscription;
use App\Models\Plan;

class CheckoutController extends Controller
{
    public function paypalSession(Request $request)
    {
        $request->validate([
            'plan_name' => 'required|string',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
        ]);

        $church = $request->user()->church;
        // In a real app we'd fetch the Plan from DB using name or ID.
        // If Plan table uses names like 'Basic', we can do:
        $plan = Plan::where('name', $request->plan_name)->firstOrFail();

        // Fetch or create a pending subscription to attach to the custom_id
        $subscription = Subscription::firstOrCreate(
            ['church_id' => $church->id, 'status' => 'pending'],
            ['plan_id' => $plan->id, 'start_date' => now(), 'end_date' => now()->addMonth()]
        );

        $subscription->update(['plan_id' => $plan->id]);

        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();

        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => $request->success_url,
                "cancel_url" => $request->cancel_url,
            ],
            "purchase_units" => [
                [
                    "reference_id" => (string)$subscription->id,
                    "custom_id" => (string)$subscription->id,
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => number_format($plan->price, 2, '.', '')
                    ]
                ]
            ]
        ]);

        if (isset($response['id']) && $response['id'] != null) {
            foreach ($response['links'] as $links) {
                if ($links['rel'] == 'approve') {
                    return response()->json(['url' => $links['href']]);
                }
            }
        }

        return response()->json(['error' => 'Unable to create PayPal order.', 'details' => $response], 500);
    }
}
