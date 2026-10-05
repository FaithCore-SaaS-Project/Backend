<?php

namespace Tests\Feature\Api;

use App\Models\Church;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * WebhookTest
 *
 * Tests the payment webhook endpoints (Stripe, PayHere, PayPal).
 * Covers:
 * - Signature verification (Audit Issue #18 — PayPal has none)
 * - Idempotency (duplicate transaction prevention)
 * - Correct subscription activation after payment
 * - Correct subscription expiry after failed payment
 */
class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected Church $church;
    protected Plan $plan;
    protected Subscription $subscription;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        config([
            'services.payhere.secret' => 'PAYHERE_TEST_SECRET',
            'services.stripe.webhook_secret' => 'whsec_test_secret_key',
        ]);

        $this->church = Church::create([
            'church_name'     => 'Webhook Church',
            'email'           => 'webhook@church.com',
            'phone'           => '0111111111',
            'pastor_name'     => 'Pastor Hook',
            'registration_no' => 'WHK-001',
            'address'         => '1 Hook Street',
            'city'            => 'Colombo',
            'country'         => 'Sri Lanka',
            'status'          => 'active',
        ]);

        $this->plan = Plan::create([
            'name'             => 'Growth',
            'price'            => 29.99,
            'member_limit'     => 500,
            'user_limit'       => 10,
            'storage_limit_mb' => 1024,
            'features'         => json_encode([]),
        ]);

        $this->adminUser = User::factory()->create([
            'church_id'  => $this->church->id,
            'email'      => 'admin@webhook.com',
            'first_name' => 'Admin',
            'last_name'  => 'Hook',
        ]);

        // Subscription in 'expired' state — waiting for payment/upgrade
        $this->subscription = Subscription::withoutGlobalScopes()->create([
            'church_id'  => $this->church->id,
            'plan_id'    => $this->plan->id,
            'status'     => 'expired',
            'start_date' => now(),
            'end_date'   => now()->addMonth(),
            'amount'     => 0,
        ]);
    }

    /** Helper: Build a valid Stripe signature header */
    private function buildStripeSignature(string $payload, string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);
        return "t={$timestamp},v1={$signature}";
    }

    // =========================================================================
    // PAYHERE WEBHOOK
    // =========================================================================

    /** @test */
    public function test_payhere_webhook_activates_subscription_on_success()
    {
        $secret = 'PAYHERE_TEST_SECRET';
        config(['services.payhere.secret' => $secret]);

        $merchantId    = 'TEST_MERCHANT';
        $orderId       = (string) $this->subscription->id;
        $amount        = '29.99';
        $currency      = 'LKR';
        $statusCode    = '2'; // Success
        $paymentId     = 'PAYHERE_TXN_' . uniqid();
        $md5sig        = strtoupper(md5($merchantId . $orderId . $amount . $currency . $statusCode . strtoupper(md5($secret))));

        $response = $this->postJson('/api/webhooks/payhere', [
            'merchant_id'       => $merchantId,
            'order_id'          => $orderId,
            'payhere_amount'    => $amount,
            'payhere_currency'  => $currency,
            'status_code'       => $statusCode,
            'md5sig'            => $md5sig,
            'payment_id'        => $paymentId,
        ]);

        $response->assertStatus(200);

        // Subscription must now be 'active'
        $this->assertEquals('active', $this->subscription->fresh()->status);

        // Payment record must be created
        $this->assertDatabaseHas('payments', [
            'subscription_id' => $this->subscription->id,
            'gateway'         => 'payhere',
            'transaction_id'  => $paymentId,
            'status'          => 'success',
        ]);

        // Invoice must be generated
        $this->assertDatabaseHas('invoices', [
            'church_id'       => $this->church->id,
            'subscription_id' => $this->subscription->id,
        ]);
    }

    /** @test */
    public function test_payhere_webhook_rejected_with_invalid_md5_signature()
    {
        $response = $this->postJson('/api/webhooks/payhere', [
            'merchant_id'      => 'TEST_MERCHANT',
            'order_id'         => (string) $this->subscription->id,
            'payhere_amount'   => '29.99',
            'payhere_currency' => 'LKR',
            'status_code'      => '2',
            'md5sig'           => 'INVALID_SIGNATURE_HERE',
            'payment_id'       => 'FAKE_TXN_001',
        ]);

        $response->assertStatus(400);

        // Subscription must still be 'expired'
        $this->assertEquals('expired', $this->subscription->fresh()->status);

        // No payment record
        $this->assertDatabaseMissing('payments', ['subscription_id' => $this->subscription->id]);
    }

    /** @test */
    public function test_payhere_failed_payment_expires_subscription()
    {
        // First activate the subscription
        $this->subscription->update(['status' => 'active']);

        $secret     = 'PAYHERE_TEST_SECRET';
        $merchantId = 'TEST_MERCHANT';
        $orderId    = (string) $this->subscription->id;
        $amount     = '29.99';
        $currency   = 'LKR';
        $statusCode = '-1'; // Failed
        $md5sig     = strtoupper(md5($merchantId . $orderId . $amount . $currency . $statusCode . strtoupper(md5($secret))));

        $this->postJson('/api/webhooks/payhere', [
            'merchant_id'      => $merchantId,
            'order_id'         => $orderId,
            'payhere_amount'   => $amount,
            'payhere_currency' => $currency,
            'status_code'      => $statusCode,
            'md5sig'           => $md5sig,
            'payment_id'       => 'FAILED_TXN_001',
        ]);

        // Subscription must be expired after failed payment
        $this->assertEquals('expired', $this->subscription->fresh()->status);
    }

    // =========================================================================
    // STRIPE WEBHOOK
    // =========================================================================

    /** @test */
    public function test_stripe_webhook_activates_subscription_on_checkout_completed()
    {
        $webhookSecret = 'whsec_test_secret_key';
        config(['services.stripe.webhook_secret' => $webhookSecret]);

        $transactionId = 'cs_test_' . uniqid();

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id'                   => $transactionId,
                    'client_reference_id'  => (string) $this->subscription->id,
                    'amount_total'         => 2999, // $29.99 in cents
                    'currency'             => 'usd',
                ],
            ],
        ]);

        $sigHeader = $this->buildStripeSignature($payload, $webhookSecret);

        $response = $this->postJson('/api/webhooks/stripe', json_decode($payload, true), [
            'Stripe-Signature' => $sigHeader,
            'Content-Type'     => 'application/json',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('active', $this->subscription->fresh()->status);

        $this->assertDatabaseHas('payments', [
            'subscription_id' => $this->subscription->id,
            'gateway'         => 'stripe',
            'transaction_id'  => $transactionId,
            'status'          => 'success',
        ]);
    }

    /** @test */
    public function test_stripe_webhook_rejected_with_invalid_signature()
    {
        $transactionId = 'cs_test_fake';
        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id'                  => $transactionId,
                'client_reference_id' => (string) $this->subscription->id,
                'amount_total'        => 2999,
                'currency'            => 'usd',
            ]],
        ]);

        $response = $this->postJson('/api/webhooks/stripe', json_decode($payload, true), [
            'Stripe-Signature' => 't=12345,v1=invalidsignature',
            'Content-Type'     => 'application/json',
        ]);

        $response->assertStatus(400);
        $this->assertEquals('expired', $this->subscription->fresh()->status);
    }

    /** @test */
    public function test_stripe_webhook_rejected_with_missing_signature_header()
    {
        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id'                  => 'cs_test_nosig',
                'client_reference_id' => (string) $this->subscription->id,
                'amount_total'        => 2999,
                'currency'            => 'usd',
            ]],
        ]);

        // No Stripe-Signature header at all
        $response = $this->postJson('/api/webhooks/stripe', json_decode($payload, true));

        $response->assertStatus(400);
    }

    // =========================================================================
    // IDEMPOTENCY
    // =========================================================================

    /** @test */
    public function test_webhook_is_idempotent_duplicate_transaction_not_processed_twice()
    {
        $secret        = 'PAYHERE_TEST_SECRET';
        $merchantId    = 'TEST_MERCHANT';
        $orderId       = (string) $this->subscription->id;
        $amount        = '29.99';
        $currency      = 'LKR';
        $statusCode    = '2';
        $paymentId     = 'IDEMPOTENT_TXN_001';
        $md5sig        = strtoupper(md5($merchantId . $orderId . $amount . $currency . $statusCode . strtoupper(md5($secret))));

        $payload = [
            'merchant_id'      => $merchantId,
            'order_id'         => $orderId,
            'payhere_amount'   => $amount,
            'payhere_currency' => $currency,
            'status_code'      => $statusCode,
            'md5sig'           => $md5sig,
            'payment_id'       => $paymentId,
        ];

        // Send the same webhook twice
        $this->postJson('/api/webhooks/payhere', $payload)->assertStatus(200);
        $this->postJson('/api/webhooks/payhere', $payload)->assertStatus(200);

        // Only ONE payment record must exist
        $this->assertEquals(1, Payment::withoutGlobalScopes()
            ->where('transaction_id', $paymentId)->count());
    }

    // =========================================================================
    // SUBSCRIPTION LIFECYCLE
    // =========================================================================

    /** @test */
    public function test_payment_correctly_determines_monthly_billing_cycle()
    {
        $secret     = 'PAYHERE_TEST_SECRET';
        $merchantId = 'TEST_MERCHANT';
        $orderId    = (string) $this->subscription->id;
        $amount     = '29.99'; // Monthly price
        $currency   = 'LKR';
        $statusCode = '2';
        $md5sig     = strtoupper(md5($merchantId . $orderId . $amount . $currency . $statusCode . strtoupper(md5($secret))));

        $this->postJson('/api/webhooks/payhere', [
            'merchant_id'      => $merchantId,
            'order_id'         => $orderId,
            'payhere_amount'   => $amount,
            'payhere_currency' => $currency,
            'status_code'      => $statusCode,
            'md5sig'           => $md5sig,
            'payment_id'       => 'MONTHLY_TXN_001',
        ]);

        $updatedSubscription = $this->subscription->fresh();

        // Monthly: end_date should be ~1 month from now
        $this->assertTrue(
            $updatedSubscription->end_date->between(now()->addDays(28), now()->addDays(32)),
            'Monthly subscription end_date should be approximately 1 month from now'
        );
    }

    /** @test */
    public function test_successful_payment_expires_older_active_subscriptions()
    {
        // Create an older active subscription (shouldn't be possible, but just in case)
        $olderSubscription = Subscription::withoutGlobalScopes()->create([
            'church_id'  => $this->church->id,
            'plan_id'    => $this->plan->id,
            'status'     => 'active', // Already active
            'start_date' => now()->subMonths(2),
            'end_date'   => now()->addDays(5),
            'amount'     => 29.99,
        ]);

        $secret     = 'PAYHERE_TEST_SECRET';
        $merchantId = 'TEST_MERCHANT';
        $orderId    = (string) $this->subscription->id;
        $amount     = '29.99';
        $currency   = 'LKR';
        $statusCode = '2';
        $md5sig     = strtoupper(md5($merchantId . $orderId . $amount . $currency . $statusCode . strtoupper(md5($secret))));

        $this->postJson('/api/webhooks/payhere', [
            'merchant_id'      => $merchantId,
            'order_id'         => $orderId,
            'payhere_amount'   => $amount,
            'payhere_currency' => $currency,
            'status_code'      => $statusCode,
            'md5sig'           => $md5sig,
            'payment_id'       => 'NEW_TXN_001',
        ]);

        // The older subscription must now be expired
        $this->assertEquals('expired', $olderSubscription->fresh()->status);

        // The new subscription must be active
        $this->assertEquals('active', $this->subscription->fresh()->status);
    }
}
