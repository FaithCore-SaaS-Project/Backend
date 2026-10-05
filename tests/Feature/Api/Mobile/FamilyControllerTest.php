<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Church;
use App\Models\Family;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FamilyControllerTest
 *
 * Tests the mobile family management endpoints.
 * Covers Audit Issue #7: FamilyController creates members without member_no (DB crash).
 */
class FamilyControllerTest extends TestCase
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
            'church_name'     => 'Family Church',
            'email'           => 'family@church.com',
            'phone'           => '0111111111',
            'pastor_name'     => 'Pastor Family',
            'registration_no' => 'FAM-001',
            'address'         => '1 Family Lane',
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
            'features'         => json_encode(['mobile' => ['family' => true]]),
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
            'email'     => 'familyuser@test.com',
        ]);

        $this->member = Member::withoutGlobalScopes()->create([
            'church_id'  => $this->church->id,
            'member_no'  => 'MBR-F001',
            'first_name' => 'Family',
            'last_name'  => 'User',
            'email'      => 'familyuser@test.com',
            'phone'      => '0771111111',
            'gender'     => 'male',
            'status'     => 'active',
        ]);
    }

    // =========================================================================
    // MY FAMILY
    // =========================================================================

    /** @test */
    public function test_my_family_returns_null_when_no_family_linked()
    {
        // member has no family_id
        $response = $this->actingAs($this->user)->getJson('/api/mobile/family');

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'data' => null]);
    }

    /** @test */
    public function test_my_family_returns_family_group_with_members()
    {
        $family = Family::withoutGlobalScopes()->create([
            'church_id'   => $this->church->id,
            'family_name' => 'User Family',
        ]);

        $this->member->update(['family_id' => $family->id]);

        $childMember = Member::withoutGlobalScopes()->create([
            'church_id'  => $this->church->id,
            'family_id'  => $family->id,
            'member_no'  => 'MBR-F002',
            'first_name' => 'Child',
            'last_name'  => 'User',
            'gender'     => 'male',
            'status'     => 'active',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/mobile/family');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertEquals($family->id, $response->json('data.id'));
        $this->assertNotEmpty($response->json('data.members'));
    }

    /** @test */
    public function test_my_family_returns_404_message_when_no_member_profile()
    {
        $userNoMember = User::factory()->create(['church_id' => $this->church->id]);

        $response = $this->actingAs($userNoMember)->getJson('/api/mobile/family');

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'data' => null]);
    }

    // =========================================================================
    // ADD FAMILY MEMBER
    // =========================================================================

    /**
     * @test
     * Audit Issue #7: Adding a family member must NOT crash due to missing member_no.
     * The controller must auto-generate member_no for the new family member.
     */
    public function test_add_family_member_auto_generates_member_no()
    {
        $response = $this->actingAs($this->user)->postJson('/api/mobile/family/members', [
            'first_name'   => 'Spouse',
            'last_name'    => 'User',
            'relationship' => 'Spouse',
            'gender'       => 'Female',
        ]);

        $response->assertStatus(201);

        // The new member must have a member_no (not null/empty)
        $newMember = Member::withoutGlobalScopes()->where('first_name', 'Spouse')
                            ->where('church_id', $this->church->id)
                            ->first();

        $this->assertNotNull($newMember, 'New family member must be created in DB');
        $this->assertNotEmpty($newMember->member_no, 'member_no must be auto-generated, not empty');
    }

    /** @test */
    public function test_add_family_member_creates_family_group_if_none_exists()
    {
        // member has no family_id yet
        $this->assertNull($this->member->family_id);

        $response = $this->actingAs($this->user)->postJson('/api/mobile/family/members', [
            'first_name'   => 'Child',
            'last_name'    => 'User',
            'relationship' => 'Child',
            'gender'       => 'Male',
        ]);

        $response->assertStatus(201);

        // A new Family must have been created and linked to primary member
        $this->member->refresh();
        $this->assertNotNull($this->member->family_id, 'Primary member must be linked to new family');

        $this->assertDatabaseHas('families', [
            'id'       => $this->member->family_id,
            'church_id' => $this->church->id,
        ]);
    }

    /** @test */
    public function test_add_family_member_reuses_existing_family_group()
    {
        $existingFamily = Family::withoutGlobalScopes()->create([
            'church_id'   => $this->church->id,
            'family_name' => 'User Family',
        ]);
        $this->member->update(['family_id' => $existingFamily->id]);

        $this->actingAs($this->user)->postJson('/api/mobile/family/members', [
            'first_name'   => 'Brother',
            'last_name'    => 'User',
            'relationship' => 'Sibling',
            'gender'       => 'Male',
        ]);

        // Must still be 1 family — no new family created
        $this->assertEquals(1, Family::withoutGlobalScopes()->where('church_id', $this->church->id)->count());

        // New member must be linked to existing family
        $this->assertDatabaseHas('members', [
            'first_name' => 'Brother',
            'family_id'  => $existingFamily->id,
        ]);
    }

    /** @test */
    public function test_add_family_member_requires_first_name_and_relationship()
    {
        $response = $this->actingAs($this->user)->postJson('/api/mobile/family/members', [
            'last_name' => 'OnlyLast',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['first_name', 'relationship']);
    }

    /** @test */
    public function test_add_family_member_validates_relationship_values()
    {
        $response = $this->actingAs($this->user)->postJson('/api/mobile/family/members', [
            'first_name'   => 'Invalid',
            'last_name'    => 'Relation',
            'relationship' => 'Enemy', // Not in allowed list
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['relationship']);
    }

    /** @test */
    public function test_add_family_member_fails_without_member_profile()
    {
        $userNoMember = User::factory()->create(['church_id' => $this->church->id]);

        $response = $this->actingAs($userNoMember)->postJson('/api/mobile/family/members', [
            'first_name'   => 'Nobody',
            'last_name'    => 'Here',
            'relationship' => 'Spouse',
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function test_add_family_member_blocked_when_feature_disabled()
    {
        // Plan without family feature
        $basicPlan = Plan::create([
            'name'             => 'Basic',
            'price'            => 4.99,
            'member_limit'     => 50,
            'user_limit'       => 5,
            'storage_limit_mb' => 512,
            'features'         => json_encode(['mobile' => ['family' => false]]),
        ]);

        Subscription::withoutGlobalScopes()->where('church_id', $this->church->id)->update(['status' => 'expired']);
        Subscription::withoutGlobalScopes()->create([
            'church_id'  => $this->church->id,
            'plan_id'    => $basicPlan->id,
            'status'     => 'active',
            'start_date' => now()->subDay(),
            'end_date'   => now()->addMonth(),
            'amount'     => 4.99,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/mobile/family/members', [
            'first_name'   => 'Blocked',
            'last_name'    => 'Feature',
            'relationship' => 'Child',
        ]);

        $response->assertStatus(403);
    }
}
