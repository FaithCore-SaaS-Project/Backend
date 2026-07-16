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

    public function payhereSession(Request $request)
    {
        $request->validate([
            'plan_name' => 'required|string',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
        ]);

        $church = $request->user()->church;
        $plan = Plan::where('name', $request->plan_name)->firstOrFail();

        // Fetch or create a pending subscription
        $subscription = Subscription::firstOrCreate(
            ['church_id' => $church->id, 'status' => 'pending'],
            [
                'plan_id' => $plan->id,
                'amount' => $plan->price,
                'start_date' => now(),
                'end_date' => now()->addMonth()
            ]
        );

        $subscription->update([
            'plan_id' => $plan->id,
            'amount' => $plan->price
        ]);

        $merchantId = env('PAYHERE_MERCHANT_ID');
        $merchantSecret = env('PAYHERE_SECRET');
        $currency = 'LKR';
        // In this implementation, the plan price in DB is assumed to be in LKR or USD, adjust accordingly.
        // For PayHere local, we will use LKR, but let's just use the plan's raw price.
        $amount = number_format($plan->price, 2, '.', '');
        $orderId = (string)$subscription->id;

        // Generate the PayHere Hash
        $hash = strtoupper(md5($merchantId . $orderId . $amount . $currency . strtoupper(md5($merchantSecret))));

        return response()->json([
            'merchant_id' => $merchantId,
            'return_url' => $request->success_url,
            'cancel_url' => $request->cancel_url,
            'notify_url' => config('app.url') . '/api/webhooks/payhere',
            'order_id' => $orderId,
            'items' => 'FaithCore ' . $plan->name . ' Plan',
            'currency' => $currency,
            'amount' => $amount,
            'first_name' => $request->user()->name,
            'last_name' => '',
            'email' => $request->user()->email,
            'phone' => '0770000000',
            'address' => 'Sri Lanka',
            'city' => 'Colombo',
            'country' => 'Sri Lanka',
            'hash' => $hash,
            'payhere_url' => env('PAYHERE_ENV', 'sandbox') === 'live' ? 'https://www.payhere.lk/pay/checkout' : 'https://sandbox.payhere.lk/pay/checkout'
        ]);
    }
}
