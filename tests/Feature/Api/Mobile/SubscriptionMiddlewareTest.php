<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Church;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SubscriptionMiddlewareTest
 *
 * Tests the SubscriptionMiddleware gate for all protected mobile endpoints.
 * Covers Audit Bug #5: SubscriptionMiddleware ignores end_date.
 *
 * A subscription with status='active' but an expired end_date must be BLOCKED.
 */
class SubscriptionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected Church $church;
    protected Plan $plan;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->church = Church::create([
            'church_name'     => 'Test Church',
            'email'           => 'sub@test.com',
            'phone'           => '0111111111',
            'pastor_name'     => 'Pastor Sub',
            'registration_no' => 'SUB-001',
            'address'         => '10 Sub Street',
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
            'features'         => json_encode(['mobile' => ['giving' => true, 'push_notifications' => true]]),
        ]);

        $this->user = User::factory()->create([
            'church_id' => $this->church->id,
        ]);
    }

    /** Helper: create a subscription with custom overrides */
    private function createSubscription(array $overrides = []): Subscription
    {
        return Subscription::withoutGlobalScopes()->create(array_merge([
            'church_id'  => $this->church->id,
            'plan_id'    => $this->plan->id,
            'status'     => 'active',
            'start_date' => now()->subMonth(),
            'end_date'   => now()->addMonth(),
            'amount'     => 29.99,
        ], $overrides));
    }

    // =========================================================================
    // VALID SUBSCRIPTION — Must Pass Through
    // =========================================================================

    /** @test */
    public function test_valid_active_subscription_allows_access()
    {
        $this->createSubscription(['status' => 'active', 'end_date' => now()->addMonth()]);

        $response = $this->actingAs($this->user)->getJson('/api/mobile/members');

        $response->assertStatus(200);
    }

    // =========================================================================
    // CANCELLED SUBSCRIPTION
    // =========================================================================

    /** @test */
    public function test_cancelled_subscription_returns_403()
    {
        $this->createSubscription(['status' => 'cancelled', 'end_date' => now()->addMonth()]);

        $response = $this->actingAs($this->user)->getJson('/api/mobile/members');

        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'Subscription expired or cancelled. Please upgrade your plan to restore access.']);
    }

    // =========================================================================
    // EXPIRED SUBSCRIPTION (status field)
    // =========================================================================

    /** @test */
    public function test_expired_status_subscription_returns_403()
    {
        $this->createSubscription(['status' => 'expired', 'end_date' => now()->subDay()]);

        $response = $this->actingAs($this->user)->getJson('/api/mobile/members');

        $response->assertStatus(403);
    }

    // =========================================================================
    // BUG #5: Status='active' but end_date has passed
    // =========================================================================

    /**
     * @test
     * Audit Bug #5: Subscription with status='active' but end_date in the past
     * must be BLOCKED. The middleware must check end_date, not just status string.
     *
     * Scenario: Payment webhook failed silently, DB still has status='active'
     * but the billing period ended 3 days ago.
     */
    public function test_active_status_but_past_end_date_is_blocked()
    {
        $this->createSubscription([
            'status'     => 'active',        // Status still says active...
            'end_date'   => now()->subDays(3), // ...but expired 3 days ago!
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/mobile/members');

        // This SHOULD return 403 — if it returns 200, Bug #5 is confirmed unfixed
        $response->assertStatus(403);
    }

    /** @test */
    public function test_active_status_with_end_date_today_is_allowed()
    {
        // A subscription expiring today (end of day) should still be allowed
        $this->createSubscription([
            'status'   => 'active',
            'end_date' => now()->toDateString(), // today
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/mobile/members');

        $response->assertStatus(200);
    }

    // =========================================================================
    // NO SUBSCRIPTION AT ALL
    // =========================================================================

    /** @test */
    public function test_no_subscription_at_all_returns_403()
    {
        // No subscription created for this church — access must be denied
        $response = $this->actingAs($this->user)->getJson('/api/mobile/members');

        $response->assertStatus(403);
    }

    // =========================================================================
    // TRIALING SUBSCRIPTION
    // =========================================================================

    /** @test */
    public function test_active_subscription_with_future_end_date_allows_access()
    {
        $this->createSubscription(['status' => 'active', 'end_date' => now()->addDays(14)]);

        $response = $this->actingAs($this->user)->getJson('/api/mobile/members');

        // Trial must be allowed
        $response->assertStatus(200);
    }

    // =========================================================================
    // UNAUTHENTICATED REQUESTS
    // =========================================================================

    /** @test */
    public function test_unauthenticated_request_returns_401_not_403()
    {
        $this->createSubscription(); // Active subscription exists

        // No actingAs — unauthenticated
        $response = $this->getJson('/api/mobile/members');

        $response->assertStatus(401);
    }
}
