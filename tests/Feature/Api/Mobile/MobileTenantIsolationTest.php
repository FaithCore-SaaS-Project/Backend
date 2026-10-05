<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Church;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Family;
use App\Models\FinanceCategory;
use App\Models\FinanceIncome;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MobileTenantIsolationTest  ⭐ MOST CRITICAL
 *
 * Verifies that ALL mobile API endpoints are strictly scoped to the
 * authenticated user's church. A user from Church A must NEVER be able
 * to read or mutate data belonging to Church B.
 *
 * Covers Audit Bugs: #2 (Mobile MemberController no tenant isolation)
 */
class MobileTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Church $churchA;
    protected Church $churchB;
    protected Plan $plan;
    protected User $userA;
    protected User $userB;
    protected Member $memberA;
    protected Member $memberB;

    protected function setUp(): void
    {
        parent::setUp();

        // ── Church A ──────────────────────────────────────────────────────────
        $this->churchA = Church::create([
            'church_name'     => 'Church Alpha',
            'email'           => 'alpha@church.com',
            'phone'           => '0111111111',
            'pastor_name'     => 'Pastor Alpha',
            'registration_no' => 'ALPHA-001',
            'address'         => '1 Alpha Road',
            'city'            => 'Colombo',
            'country'         => 'Sri Lanka',
            'status'          => 'active',
        ]);

        // ── Church B ──────────────────────────────────────────────────────────
        $this->churchB = Church::create([
            'church_name'     => 'Church Beta',
            'email'           => 'beta@church.com',
            'phone'           => '0222222222',
            'pastor_name'     => 'Pastor Beta',
            'registration_no' => 'BETA-001',
            'address'         => '2 Beta Road',
            'city'            => 'Galle',
            'country'         => 'Sri Lanka',
            'status'          => 'active',
        ]);

        // ── Shared plan & active subscriptions ────────────────────────────────
        $this->plan = Plan::create([
            'name'             => 'Growth',
            'price'            => 29.99,
            'member_limit'     => 500,
            'user_limit'       => 10,
            'storage_limit_mb' => 1024,
            'features'         => json_encode([
                'mobile' => [
                    'giving'             => true,
                    'event_registration' => true,
                    'family'             => true,
                    'push_notifications' => true,
                ],
            ]),
        ]);

        foreach ([$this->churchA->id, $this->churchB->id] as $cid) {
            Subscription::withoutGlobalScopes()->create([
                'church_id'  => $cid,
                'plan_id'    => $this->plan->id,
                'status'     => 'active',
                'start_date' => now()->subMonth(),
                'end_date'   => now()->addMonth(),
                'amount'     => 29.99,
            ]);
        }

        // ── Users ─────────────────────────────────────────────────────────────
        $this->userA = User::factory()->create([
            'church_id'  => $this->churchA->id,
            'email'      => 'usera@alpha.com',
            'first_name' => 'Alice',
            'last_name'  => 'Alpha',
        ]);

        $this->userB = User::factory()->create([
            'church_id'  => $this->churchB->id,
            'email'      => 'userb@beta.com',
            'first_name' => 'Bob',
            'last_name'  => 'Beta',
        ]);

        // ── Members ───────────────────────────────────────────────────────────
        $this->memberA = Member::withoutGlobalScopes()->create([
            'church_id'  => $this->churchA->id,
            'member_no'  => 'MBR-A001',
            'first_name' => 'Alice',
            'last_name'  => 'Alpha',
            'email'      => 'usera@alpha.com',
            'gender'     => 'female',
            'status'     => 'active',
        ]);

        $this->memberB = Member::withoutGlobalScopes()->create([
            'church_id'  => $this->churchB->id,
            'member_no'  => 'MBR-B001',
            'first_name' => 'Bob',
            'last_name'  => 'Beta',
            'email'      => 'userb@beta.com',
            'gender'     => 'male',
            'status'     => 'active',
        ]);
    }

    // =========================================================================
    // MEMBER ISOLATION
    // =========================================================================

    /** @test */
    public function test_member_list_only_returns_own_church_members()
    {
        $response = $this->actingAs($this->userA)->getJson('/api/mobile/members');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->toArray();

        $this->assertContains($this->memberA->id, $ids, 'Church A member must be visible to userA');
        $this->assertNotContains($this->memberB->id, $ids, 'Church B member must NOT be visible to userA');
    }

    /** @test */
    public function test_member_show_cannot_fetch_different_church_member_by_id()
    {
        // userA tries to fetch memberB (belongs to churchB) directly by ID
        $response = $this->actingAs($this->userA)->getJson('/api/mobile/members/' . $this->memberB->id);

        // Must return 404 (scoped out), NOT 200 with private data
        $response->assertStatus(404);
    }

    /** @test */
    public function test_member_show_can_fetch_own_church_member()
    {
        $response = $this->actingAs($this->userA)->getJson('/api/mobile/members/' . $this->memberA->id);

        $response->assertStatus(200);
        $this->assertEquals($this->memberA->id, $response->json('id'));
    }

    /** @test */
    public function test_userB_cannot_delete_userA_member()
    {
        $response = $this->actingAs($this->userB)->deleteJson('/api/mobile/members/' . $this->memberA->id);

        // Must be 404 (scoped out) or 403 — never 200/204
        $response->assertStatus(404);

        // Member still exists in DB
        $this->assertDatabaseHas('members', ['id' => $this->memberA->id]);
    }

    // =========================================================================
    // GIVING / FINANCE ISOLATION
    // =========================================================================

    /** @test */
    public function test_giving_history_only_returns_current_church_records()
    {
        // Church A income
        FinanceIncome::withoutGlobalScopes()->create([
            'church_id'   => $this->churchA->id,
            'category'    => 'Tithe',
            'amount'      => 100,
            'income_date' => now()->toDateString(),
            'method'      => 'Cash',
            'member_id'   => $this->memberA->id,
            'recorded_by' => $this->userA->id,
        ]);

        // Church B income
        FinanceIncome::withoutGlobalScopes()->create([
            'church_id'   => $this->churchB->id,
            'category'    => 'Offering',
            'amount'      => 500,
            'income_date' => now()->toDateString(),
            'method'      => 'Bank Transfer',
            'member_id'   => $this->memberB->id,
            'recorded_by' => $this->userB->id,
        ]);

        $response = $this->actingAs($this->userA)->getJson('/api/mobile/giving/history');

        $response->assertStatus(200);

        $amounts = collect($response->json('data'))->pluck('amount')->map(fn($v) => (float)$v)->toArray();

        $this->assertContains(100.0, $amounts, 'Church A giving must be visible');
        $this->assertNotContains(500.0, $amounts, 'Church B giving must NOT be visible to userA');
    }

    /** @test */
    public function test_donation_stored_with_authenticated_users_church_id()
    {
        $category = FinanceCategory::withoutGlobalScopes()->create([
            'church_id'   => $this->churchA->id,
            'name'        => 'Tithe',
            'type'        => 'income',
            'status'      => 'active',
            'description' => 'Monthly Tithe',
        ]);

        $response = $this->actingAs($this->userA)->postJson('/api/mobile/give', [
            'amount'         => 250.00,
            'category_id'   => $category->id,
            'payment_method' => 'Cash',
            'note'           => 'Monthly tithe',
        ]);

        $response->assertStatus(201);

        // Donation must be stored under Church A — not any other church
        $this->assertDatabaseHas('finance_income', [
            'church_id' => $this->churchA->id,
            'amount'    => 250.00,
        ]);
    }

    // =========================================================================
    // EVENT ISOLATION
    // =========================================================================

    /** @test */
    public function test_events_list_only_returns_own_church_events()
    {
        $eventA = Event::withoutGlobalScopes()->create([
            'church_id'  => $this->churchA->id,
            'event_name' => 'Alpha Sunday Service',
            'event_date' => now()->addDays(7)->toDateString(),
            'venue'      => 'Alpha Sanctuary',
            'type'       => 'Service',
            'status'     => 'scheduled',
        ]);

        $eventB = Event::withoutGlobalScopes()->create([
            'church_id'  => $this->churchB->id,
            'event_name' => 'Beta Youth Camp',
            'event_date' => now()->addDays(14)->toDateString(),
            'venue'      => 'Beta Hall',
            'type'       => 'Camp',
            'status'     => 'scheduled',
        ]);

        $response = $this->actingAs($this->userA)->getJson('/api/mobile/events');

        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('event_name')->toArray();

        $this->assertContains('Alpha Sunday Service', $names);
        $this->assertNotContains('Beta Youth Camp', $names);
    }

    /** @test */
    public function test_userA_cannot_register_for_church_b_event()
    {
        $eventB = Event::withoutGlobalScopes()->create([
            'church_id'    => $this->churchB->id,
            'event_name'   => 'Beta Exclusive Event',
            'event_date'   => now()->addDays(5)->toDateString(),
            'venue'        => 'Beta Hall',
            'type'         => 'Conference',
            'status'       => 'scheduled',
            'max_capacity' => 100,
            'attendees'    => 0,
        ]);

        $response = $this->actingAs($this->userA)->postJson("/api/mobile/events/{$eventB->id}/register");

        // Must be 404 (event not found in church scope) or 403
        $response->assertStatus(404);
    }

    // =========================================================================
    // FAMILY ISOLATION
    // =========================================================================

    /** @test */
    public function test_family_endpoint_scoped_to_authenticated_users_church()
    {
        // Create a family and link memberA to it
        $familyA = Family::withoutGlobalScopes()->create([
            'church_id'   => $this->churchA->id,
            'family_name' => 'Alpha Family',
        ]);

        $this->memberA->update(['family_id' => $familyA->id]);

        // Create a family for Church B
        $familyB = Family::withoutGlobalScopes()->create([
            'church_id'   => $this->churchB->id,
            'family_name' => 'Beta Secret Family',
        ]);

        $this->memberB->update(['family_id' => $familyB->id]);

        // userA requests their family
        $response = $this->actingAs($this->userA)->getJson('/api/mobile/family');

        $response->assertStatus(200);
        $this->assertNotEquals($familyB->id, $response->json('data.id'));
        $this->assertEquals($familyA->id, $response->json('data.id'));
    }

    // =========================================================================
    // ANNOUNCEMENT ISOLATION
    // =========================================================================

    /** @test */
    public function test_announcements_scoped_to_authenticated_users_church()
    {
        \App\Models\Announcement::withoutGlobalScopes()->create([
            'church_id'    => $this->churchA->id,
            'title'        => 'Alpha Private Announcement',
            'content'      => 'Secret Alpha info',
            'is_published' => true,
        ]);

        \App\Models\Announcement::withoutGlobalScopes()->create([
            'church_id'    => $this->churchB->id,
            'title'        => 'Beta Private Announcement',
            'content'      => 'Secret Beta info',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->userA)->getJson('/api/mobile/announcements');

        $response->assertStatus(200);

        $titles = collect($response->json('data'))->pluck('title')->toArray();

        $this->assertContains('Alpha Private Announcement', $titles);
        $this->assertNotContains('Beta Private Announcement', $titles);
    }

    // =========================================================================
    // DASHBOARD ISOLATION
    // =========================================================================

    /** @test */
    public function test_dashboard_stats_reflect_only_own_church_data()
    {
        // Church A: 3 members, 1 event
        Member::withoutGlobalScopes()->create(['church_id' => $this->churchA->id, 'member_no' => 'MBR-A002', 'first_name' => 'Eve', 'last_name' => 'Alpha', 'gender' => 'female', 'status' => 'active']);
        Member::withoutGlobalScopes()->create(['church_id' => $this->churchA->id, 'member_no' => 'MBR-A003', 'first_name' => 'Grace', 'last_name' => 'Alpha', 'gender' => 'female', 'status' => 'active']);

        // Church B: 10 members (should NOT count in userA stats)
        for ($i = 1; $i <= 10; $i++) {
            Member::withoutGlobalScopes()->create([
                'church_id'  => $this->churchB->id,
                'member_no'  => "MBR-B100{$i}",
                'first_name' => "Member{$i}",
                'last_name'  => 'Beta',
                'gender'     => 'male',
                'status'     => 'active',
            ]);
        }

        $response = $this->actingAs($this->userA)->getJson('/api/mobile/dashboard/stats');

        $response->assertStatus(200);

        // userA's church has 3 active members (memberA + 2 more added above)
        $this->assertEquals(3, $response->json('data.stats.total_members'));
    }
}
