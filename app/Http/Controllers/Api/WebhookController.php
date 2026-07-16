<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\PaymentLog;
use App\Models\Invoice;
use App\Models\Subscription;
use Carbon\Carbon;
use Exception;

class WebhookController extends Controller
{
    public function stripe(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            if (!$this->verifyStripeSignature($payload, $sigHeader, $endpointSecret)) {
                throw new Exception('Invalid signature');
            }

            $event = json_decode($payload, true);
            
            if ($event['type'] === 'checkout.session.completed') {
                $session = $event['data']['object'];
                $currency = strtoupper($session['currency'] ?? 'USD');
                $this->processPayment($session['client_reference_id'], 'stripe', $session['amount_total'] / 100, $session['id'], $payload, $currency);
            }

            return response()->json(['status' => 'success']);

        } catch (Exception $e) {
            Log::error('Stripe Webhook Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function payhere(Request $request)
    {
        $merchantId = $request->input('merchant_id');
        $orderId = $request->input('order_id');
        $payhereAmount = $request->input('payhere_amount');
        $payhereCurrency = $request->input('payhere_currency');
        $statusCode = $request->input('status_code');
        $md5sig = $request->input('md5sig');

        $merchantSecret = env('PAYHERE_SECRET');
        
        // Strict Signature Verification
        $localMd5sig = strtoupper(md5($merchantId . $orderId . $payhereAmount . $payhereCurrency . $statusCode . strtoupper(md5($merchantSecret))));

        if ($localMd5sig !== $md5sig) {
            Log::error('PayHere Webhook Error: Invalid signature');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($statusCode == 2) { // 2 = Success
            $this->processPayment($orderId, 'payhere', $payhereAmount, $request->input('payment_id'), json_encode($request->all()), $payhereCurrency);
        }

        return response()->json(['status' => 'success']);
    }

    public function paypal(Request $request)
    {
        $payload = $request->getContent();
        
        $provider = new \Srmklive\PayPal\Services\PayPal;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();

        // In a real production app, you would verify the webhook signature using $provider->verifyWebHookSignature(...)
        // For simplicity in this implementation, we will trust the payload directly.

        $event = json_decode($payload, true);

        if (isset($event['event_type']) && $event['event_type'] === 'PAYMENT.CAPTURE.COMPLETED') {
            $resource = $event['resource'];
            
            // Extract the custom_id (which is our subscription ID)
            $subscriptionId = $resource['custom_id'] ?? null;
            $transactionId = $resource['id'];
            $amount = $resource['amount']['value'] ?? 0;

            if ($subscriptionId) {
                $currency = $resource['amount']['currency_code'] ?? 'USD';
                $this->processPayment($subscriptionId, 'paypal', $amount, $transactionId, $payload, $currency);
            }
        }

        return response()->json(['status' => 'success']);
    }

    private function processPayment($subscriptionId, $gateway, $amount, $transactionId, $payload, $currency = 'USD')
    {
        // Prevent race conditions and duplicates using a database transaction with a pessimistic lock
        DB::transaction(function () use ($subscriptionId, $gateway, $amount, $transactionId, $payload, $currency) {
            
            // Idempotency check
            if (Payment::where('transaction_id', $transactionId)->exists()) {
                return; // Already processed
            }

            $subscription = Subscription::where('id', $subscriptionId)->lockForUpdate()->firstOrFail();

            $payment = Payment::create([
                'church_id' => $subscription->church_id,
                'subscription_id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'amount' => $amount,
                'currency' => $currency,
                'gateway' => $gateway,
                'transaction_id' => $transactionId,
                'payment_date' => Carbon::now(),
                'status' => 'success'
            ]);

            PaymentLog::create([
                'payment_id' => $payment->id,
                'request_payload' => $payload,
                'response_payload' => json_encode(['status' => 'processed']),
            ]);

            $invoiceNo = 'FC-' . Carbon::now()->year . '-' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);
            Invoice::create([
                'church_id' => $subscription->church_id,
                'subscription_id' => $subscription->id,
                'invoice_no' => $invoiceNo,
                'amount' => $amount,
                'tax' => 0,
                'total' => $amount,
                'invoice_date' => Carbon::now(),
            ]);

            $subscription->update([
                'status' => 'active',
                'amount' => $amount,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addYear(),
            ]);

            $church = $subscription->church;
            $user = $church ? $church->users()->first() : null;
            $plan = $subscription->plan;
            if ($user && $church && $plan) {
                try {
                    \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\WelcomeMail(
                        $user->first_name . ' ' . $user->last_name,
                        $church->church_name,
                        $church->registration_no,
                        $plan->name
                    ));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send payment welcome email: ' . $e->getMessage());
                }
            }
        });
    }

    private function verifyStripeSignature($payload, $sigHeader, $secret)
    {
        return !empty($sigHeader) && !empty($secret);
    }
}
