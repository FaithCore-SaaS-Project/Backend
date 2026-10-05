<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Church;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * MobileAuthTest
 *
 * Tests: Mobile login, logout, profile update, avatar upload, push token.
 * Covers audit bug #1 (wrong login controller) and bug #3 (token null on profile update).
 */
class MobileAuthTest extends TestCase
{
    use RefreshDatabase;

    protected Church $church;
    protected Plan $plan;
    protected Subscription $subscription;
    protected User $user;
    protected Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->church = Church::create([
            'church_name'     => 'Test Church',
            'email'           => 'church@test.com',
            'phone'           => '0111234567',
            'pastor_name'     => 'Pastor Test',
            'registration_no' => 'TC-001',
            'address'         => '123 Church Street',
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
                'mobile' => [
                    'push_notifications' => true,
                    'giving'             => true,
                    'event_registration' => true,
                    'family'             => true,
                ],
            ]),
        ]);

        $this->subscription = Subscription::withoutGlobalScopes()->create([
            'church_id'  => $this->church->id,
            'plan_id'    => $this->plan->id,
            'status'     => 'active',
            'start_date' => now()->subMonth(),
            'end_date'   => now()->addMonth(),
            'amount'     => 29.99,
        ]);

        $this->user = User::factory()->create([
            'church_id' => $this->church->id,
            'email'     => 'user@test.com',
            'password'  => Hash::make('password123'),
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'phone'      => '0771234567',
        ]);

        $this->member = Member::create([
            'church_id'  => $this->church->id,
            'member_no'  => 'MBR-0001',
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'user@test.com',
            'phone'      => '0771234567',
            'gender'     => 'male',
            'status'     => 'active',
        ]);
    }

    // =========================================================================
    // LOGIN TESTS
    // =========================================================================

    /**
     * @test
     * Audit Bug #1: Mobile login must return user with church and roles preloaded.
     * The web AuthController returns a different shape — mobile controller must be used.
     */
    public function test_mobile_login_returns_token_and_user_with_church()
    {
        $response = $this->postJson('/api/mobile/login', [
            'email'    => 'user@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
            'user' => [
                'id',
                'email',
                'first_name',
                'last_name',
                'church',
            ],
        ]);

        // The token must not be null
        $this->assertNotNull($response->json('token'));

        // Church must be embedded in the user object (not null)
        $this->assertNotNull($response->json('user.church'));
        $this->assertEquals($this->church->id, $response->json('user.church.id'));
    }

    /** @test */
    public function test_mobile_login_fails_with_wrong_password()
    {
        $response = $this->postJson('/api/mobile/login', [
            'email'    => 'user@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function test_mobile_login_fails_with_nonexistent_email()
    {
        $response = $this->postJson('/api/mobile/login', [
            'email'    => 'nobody@nowhere.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_mobile_login_requires_email_and_password()
    {
        $response = $this->postJson('/api/mobile/login', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    // =========================================================================
    // LOGOUT TESTS
    // =========================================================================

    /** @test */
    public function test_mobile_logout_revokes_current_token()
    {
        $token = $this->user->createToken('mobile-app-token')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/mobile/logout');

        $response->assertStatus(200);

        // Token should be deleted from the database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function test_mobile_logout_requires_authentication()
    {
        $response = $this->postJson('/api/mobile/logout');
        $response->assertStatus(401);
    }

    // =========================================================================
    // PROFILE UPDATE TESTS
    // =========================================================================

    /**
     * @test
     * Audit Bug #3: Profile update was overwriting SecureStore token with user?.token
     * (which is undefined). The response must return a valid user object and the
     * frontend should re-use the existing token, NOT user.token.
     */
    public function test_profile_update_returns_fresh_user_data()
    {
        $response = $this->actingAs($this->user)->postJson('/api/mobile/user/update', [
            'first_name' => 'UpdatedJohn',
            'last_name'  => 'UpdatedDoe',
            'phone'      => '0779999999',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Profile updated successfully.',
        ]);

        // Response data must have the updated user — no 'token' field expected here
        // (frontend must preserve its own token, not read from user object)
        $this->assertEquals('UpdatedJohn', $response->json('data.first_name'));
        $this->assertEquals('UpdatedDoe', $response->json('data.last_name'));

        // Verify DB was actually updated
        $this->assertDatabaseHas('users', [
            'id'         => $this->user->id,
            'first_name' => 'UpdatedJohn',
        ]);
    }

    /** @test */
    public function test_profile_update_also_syncs_linked_member_record()
    {
        $response = $this->actingAs($this->user)->postJson('/api/mobile/user/update', [
            'first_name' => 'SyncedName',
            'last_name'  => 'SyncedLast',
            'phone'      => '0770000001',
            'gender'     => 'Male',
            'address'    => '456 New Street',
        ]);

        $response->assertStatus(200);

        // Member record must also be updated
        $this->assertDatabaseHas('members', [
            'email'      => 'user@test.com',
            'first_name' => 'SyncedName',
            'address'    => '456 New Street',
        ]);
    }

    /** @test */
    public function test_profile_update_email_uniqueness_allows_same_user_email()
    {
        // Updating with the same email should NOT fail uniqueness validation
        $response = $this->actingAs($this->user)->postJson('/api/mobile/user/update', [
            'email' => 'user@test.com', // same as current
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function test_profile_update_email_uniqueness_blocks_another_users_email()
    {
        $anotherUser = User::factory()->create([
            'church_id' => $this->church->id,
            'email'     => 'taken@test.com',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/mobile/user/update', [
            'email' => 'taken@test.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function test_profile_update_requires_authentication()
    {
        $response = $this->postJson('/api/mobile/user/update', [
            'first_name' => 'Hacker',
        ]);
        $response->assertStatus(401);
    }

    // =========================================================================
    // PUSH TOKEN TESTS
    // =========================================================================

    /** @test */
    public function test_push_token_saved_when_feature_enabled()
    {
        $response = $this->actingAs($this->user)->postJson('/api/mobile/user/push-token', [
            'push_token' => 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxxxx]',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'id'         => $this->user->id,
            'push_token' => 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxxxx]',
        ]);
    }

    /** @test */
    public function test_push_token_requires_non_empty_string()
    {
        $response = $this->actingAs($this->user)->postJson('/api/mobile/user/push-token', [
            'push_token' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['push_token']);
    }

    /** @test */
    public function test_push_token_blocked_when_feature_disabled()
    {
        // Plan without push notifications feature
        $basicPlan = Plan::create([
            'name'             => 'Basic',
            'price'            => 9.99,
            'member_limit'     => 100,
            'user_limit'       => 5,
            'storage_limit_mb' => 512,
            'features'         => json_encode(['mobile' => ['push_notifications' => false]]),
        ]);

        Subscription::withoutGlobalScopes()->where('church_id', $this->church->id)->update(['status' => 'expired']);

        Subscription::withoutGlobalScopes()->create([
            'church_id'  => $this->church->id,
            'plan_id'    => $basicPlan->id,
            'status'     => 'active',
            'start_date' => now()->subDay(),
            'end_date'   => now()->addMonth(),
            'amount'     => 9.99,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/mobile/user/push-token', [
            'push_token' => 'ExponentPushToken[yyy]',
        ]);

        $response->assertStatus(403);
    }
}
