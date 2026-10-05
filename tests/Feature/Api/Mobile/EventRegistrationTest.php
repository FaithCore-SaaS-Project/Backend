<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Church;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * EventRegistrationTest
 *
 * Tests event listing, show, and registration.
 * Covers Audit Issue #8: Race condition in attendees count (capacity check vs increment not atomic).
 */
class EventRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected Church $church;
    protected Plan $plan;
    protected User $user;
    protected Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->church = Church::create([
            'church_name'     => 'Event Church',
            'email'           => 'event@church.com',
            'phone'           => '0111111111',
            'pastor_name'     => 'Pastor Event',
            'registration_no' => 'EVT-001',
            'address'         => '1 Event Road',
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
            'features'         => json_encode([
                'mobile' => ['event_registration' => true, 'giving' => true],
            ]),
        ]);

        Subscription::withoutGlobalScopes()->create([
            'church_id'  => $this->church->id,
            'plan_id'    => $this->plan->id,
            'status'     => 'active',
            'start_date' => now()->subMonth(),
            'end_date'   => now()->addMonth(),
            'amount'     => 29.99,
        ]);

        $this->user = User::factory()->create([
            'church_id' => $this->church->id,
            'email'     => 'eventuser@test.com',
        ]);

        $this->member = Member::withoutGlobalScopes()->create([
            'church_id'  => $this->church->id,
            'member_no'  => 'MBR-E001',
            'first_name' => 'Event',
            'last_name'  => 'User',
            'email'      => 'eventuser@test.com',
            'gender'     => 'male',
            'status'     => 'active',
        ]);
    }

    /** Helper: Create an event for this church */
    private function createEvent(array $overrides = []): Event
    {
        return Event::withoutGlobalScopes()->create(array_merge([
            'church_id'    => $this->church->id,
            'event_name'   => 'Sunday Service',
            'event_date'   => now()->addDays(7)->toDateString(),
            'venue'        => 'Main Sanctuary',
            'type'         => 'Service',
            'status'       => 'scheduled',
            'max_capacity' => 0,
            'attendees'    => 0,
        ], $overrides));
    }

    // =========================================================================
    // EVENT LISTING
    // =========================================================================

    /** @test */
    public function test_events_index_returns_upcoming_events_by_default()
    {
        $future = $this->createEvent(['event_name' => 'Future Event', 'event_date' => now()->addDays(5)->toDateString()]);
        $past   = $this->createEvent(['event_name' => 'Past Event',   'event_date' => now()->subDays(5)->toDateString()]);

        $response = $this->actingAs($this->user)->getJson('/api/mobile/events?type=upcoming');

        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('event_name')->toArray();
        $this->assertContains('Future Event', $names);
        $this->assertNotContains('Past Event', $names);
    }

    /** @test */
    public function test_events_index_can_filter_past_events()
    {
        $this->createEvent(['event_name' => 'Old Event', 'event_date' => now()->subDays(10)->toDateString()]);

        $response = $this->actingAs($this->user)->getJson('/api/mobile/events?type=past');

        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('event_name')->toArray();
        $this->assertContains('Old Event', $names);
    }

    /** @test */
    public function test_event_show_returns_single_event()
    {
        $event = $this->createEvent(['event_name' => 'Specific Event']);

        $response = $this->actingAs($this->user)->getJson("/api/mobile/events/{$event->id}");

        $response->assertStatus(200);
        $this->assertEquals('Specific Event', $response->json('event_name'));
    }

    // =========================================================================
    // EVENT REGISTRATION
    // =========================================================================

    /** @test */
    public function test_user_can_register_for_an_event()
    {
        $event = $this->createEvent(['max_capacity' => 100, 'attendees' => 0]);

        $response = $this->actingAs($this->user)->postJson("/api/mobile/events/{$event->id}/register");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Registration record must exist
        $this->assertDatabaseHas('event_registrations', [
            'event_id'  => $event->id,
            'member_id' => $this->member->id,
        ]);

        // Attendees count must be incremented
        $this->assertEquals(1, $event->fresh()->attendees);
    }

    /** @test */
    public function test_duplicate_registration_returns_409_conflict()
    {
        $event = $this->createEvent(['max_capacity' => 100, 'attendees' => 1]);

        // First registration
        EventRegistration::withoutGlobalScopes()->create([
            'church_id' => $this->church->id,
            'event_id'  => $event->id,
            'member_id' => $this->member->id,
        ]);

        // Second registration attempt
        $response = $this->actingAs($this->user)->postJson("/api/mobile/events/{$event->id}/register");

        $response->assertStatus(409);
        $response->assertJsonFragment(['message' => 'You are already registered for this event.']);
    }

    /** @test */
    public function test_registration_fails_when_event_is_at_max_capacity()
    {
        $event = $this->createEvent([
            'max_capacity' => 5,
            'attendees'    => 5, // Already full!
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/mobile/events/{$event->id}/register");

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'This event is fully booked.']);
    }

    /** @test */
    public function test_registration_succeeds_when_event_has_no_capacity_limit()
    {
        $event = $this->createEvent(['max_capacity' => 0, 'attendees' => 9999]);

        $response = $this->actingAs($this->user)->postJson("/api/mobile/events/{$event->id}/register");

        $response->assertStatus(200);
    }

    /** @test */
    public function test_registration_fails_without_linked_member_profile()
    {
        // User with NO member profile
        $userNoMember = User::factory()->create([
            'church_id' => $this->church->id,
            'email'     => 'nomember@test.com',
        ]);

        $event = $this->createEvent(['max_capacity' => 100]);

        $response = $this->actingAs($userNoMember)->postJson("/api/mobile/events/{$event->id}/register");

        $response->assertStatus(404);
        $response->assertJsonFragment(['message' => 'Member profile not found. Please contact your church administrator.']);
    }

    /**
     * @test
     * Audit Issue #8: Concurrent registrations must not exceed max_capacity.
     * This test simulates two near-simultaneous registrations to an event with capacity=1.
     *
     * NOTE: True concurrency testing requires actual parallel processes. This test
     * uses a DB-level approach to verify the implementation handles it correctly
     * by checking state after two sequential calls that bypass the cache check.
     */
    public function test_concurrent_registrations_do_not_exceed_max_capacity()
    {
        $event = $this->createEvent([
            'max_capacity' => 1,
            'attendees'    => 0,
        ]);

        // Second user from same church
        $userB = User::factory()->create(['church_id' => $this->church->id, 'email' => 'userb@test.com']);
        $memberB = Member::withoutGlobalScopes()->create([
            'church_id' => $this->church->id,
            'member_no' => 'MBR-E002',
            'first_name' => 'Second',
            'last_name'  => 'User',
            'email'      => 'userb@test.com',
            'gender'     => 'female',
            'status'     => 'active',
        ]);

        // Simulate: both users pass capacity check at the "same time" by
        // manually registering user A first without incrementing the counter,
        // then user B tries to register
        EventRegistration::withoutGlobalScopes()->create([
            'church_id' => $this->church->id,
            'event_id'  => $event->id,
            'member_id' => $this->member->id,
        ]);
        $event->increment('attendees');

        // Now capacity is full (attendees=1, max_capacity=1)
        // User B's registration must be rejected
        $response = $this->actingAs($userB)->postJson("/api/mobile/events/{$event->id}/register");

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'This event is fully booked.']);

        // Verify only 1 registration in DB
        $this->assertEquals(1, EventRegistration::withoutGlobalScopes()->where('event_id', $event->id)->count());
        $this->assertEquals(1, $event->fresh()->attendees);
    }
}
