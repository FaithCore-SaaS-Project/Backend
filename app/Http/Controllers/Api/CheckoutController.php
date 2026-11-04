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
            'is_annual' => 'nullable|boolean',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
        ]);

        $isAnnual = $request->input('is_annual', false);
        $church = $request->user()->church;
        $plan = Plan::where('name', $request->plan_name)->firstOrFail();

        $rawPrice = $isAnnual ? ($plan->price * 12 * 0.9) : $plan->price;
        $endDate = $isAnnual ? now()->addYear() : now()->addMonth();

        // Create a new pending subscription (using 'expired' status as pending)
        $subscription = Subscription::create([
            'church_id' => $church->id,
            'plan_id' => $plan->id,
            'amount' => $rawPrice,
            'start_date' => now(),
            'end_date' => $endDate,
            'status' => 'expired'
        ]);

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
                        "value" => number_format($rawPrice, 2, '.', '')
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
            'is_annual' => 'nullable|boolean',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
        ]);

        $isAnnual = $request->input('is_annual', false);
        $church = $request->user()->church;
        $plan = Plan::where('name', $request->plan_name)->firstOrFail();

        $rawPrice = $isAnnual ? ($plan->price * 12 * 0.9) : $plan->price;
        $endDate = $isAnnual ? now()->addYear() : now()->addMonth();

        // Create a new pending subscription
        $subscription = Subscription::create([
            'church_id' => $church->id,
            'plan_id' => $plan->id,
            'amount' => $rawPrice,
            'start_date' => now(),
            'end_date' => $endDate,
            'status' => 'expired'
        ]);

        $merchantId = config('services.payhere.merchant_id') ?: env('PAYHERE_MERCHANT_ID');
        $merchantSecret = config('services.payhere.secret') ?: env('PAYHERE_SECRET');
        $payhereEnv = config('services.payhere.env') ?: env('PAYHERE_ENV', 'sandbox');
        $currency = 'LKR';
        
        $amount = number_format($rawPrice, 2, '.', '');
        $orderId = (string)$subscription->id;

        // Generate the PayHere Hash
        $hash = strtoupper(md5($merchantId . $orderId . $amount . $currency . strtoupper(md5($merchantSecret))));

        // Split name into first_name and last_name ensuring neither is empty (PayHere rejects empty last_name)
        $fullName = trim($request->user()->name ?? 'Church Admin');
        $nameParts = explode(' ', $fullName, 2);
        $firstName = !empty($nameParts[0]) ? $nameParts[0] : 'Church';
        $lastName = !empty($nameParts[1]) ? $nameParts[1] : 'Admin';

        // Format a safe notify_url
        $notifyUrl = config('app.url') . '/api/webhooks/payhere';
        if (str_contains($notifyUrl, 'localhost')) {
            // PayHere rejects localhost notify URLs in some sandbox validation routines
            $notifyUrl = 'https://api.faithcore.org/api/webhooks/payhere';
        }

        return response()->json([
            'merchant_id' => $merchantId,
            'return_url' => $request->success_url,
            'cancel_url' => $request->cancel_url,
            'notify_url' => $notifyUrl,
            'order_id' => $orderId,
            'items' => 'FaithCore ' . $plan->name . ' Plan',
            'currency' => $currency,
            'amount' => $amount,
            'recurrence' => $isAnnual ? '1 Year' : '1 Month',
            'duration' => 'Forever',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $request->user()->email ?? 'admin@faithcore.org',
            'phone' => '0771234567',
            'address' => 'FaithCore Church Office',
            'city' => 'Colombo',
            'country' => 'Sri Lanka',
            'hash' => $hash,
            // Unified PayHere form POST endpoint: /pay/checkout (supports both recurring & one-time)
            'payhere_url' => $payhereEnv === 'live' ? 'https://www.payhere.lk/pay/checkout' : 'https://sandbox.payhere.lk/pay/checkout'
        ]);
    }
}
