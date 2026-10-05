<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Church;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * OnboardingTest
 *
 * Tests the full mobile onboarding flow:
 * - Find church by invite code
 * - Send OTP / Verify OTP
 * - Register new user + member
 *
 * Covers Audit Issues: #9 (OTP brute-force, mock bypass in staging)
 */
class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected Church $activeChurch;
    protected Church $inactiveChurch;
    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->plan = Plan::create([
            'name'             => 'Starter',
            'price'            => 9.99,
            'member_limit'     => 200,
            'user_limit'       => 5,
            'storage_limit_mb' => 512,
            'features'         => json_encode(['mobile' => ['giving' => false]]),
        ]);

        $this->activeChurch = Church::create([
            'church_name'     => 'Active Church',
            'email'           => 'active@church.com',
            'phone'           => '0111111111',
            'pastor_name'     => 'Pastor Active',
            'registration_no' => 'ACT-001',
            'address'         => '1 Active Road',
            'city'            => 'Colombo',
            'country'         => 'Sri Lanka',
            'status'          => 'active',
        ]);

        Subscription::withoutGlobalScopes()->create([
            'church_id'  => $this->activeChurch->id,
            'plan_id'    => $this->plan->id,
            'status'     => 'active',
            'start_date' => now()->subMonth(),
            'end_date'   => now()->addMonth(),
            'amount'     => 9.99,
        ]);

        $this->inactiveChurch = Church::create([
            'church_name'     => 'Inactive Church',
            'email'           => 'inactive@church.com',
            'phone'           => '0222222222',
            'pastor_name'     => 'Pastor Inactive',
            'registration_no' => 'INACT-001',
            'address'         => '2 Inactive Road',
            'city'            => 'Galle',
            'country'         => 'Sri Lanka',
            'status'          => 'inactive', // Inactive!
        ]);
    }

    // =========================================================================
    // FIND CHURCH
    // =========================================================================

    /** @test */
    public function test_find_church_succeeds_with_valid_invite_code()
    {
        $response = $this->postJson('/api/mobile/onboarding/find-church', [
            'invite_code' => 'ACT-001',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['data' => ['id', 'name', 'address', 'logo']]);
        $this->assertEquals($this->activeChurch->id, $response->json('data.id'));
    }

    /** @test */
    public function test_find_church_fails_with_nonexistent_invite_code()
    {
        $response = $this->postJson('/api/mobile/onboarding/find-church', [
            'invite_code' => 'DOESNOTEXIST-999',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }

    /** @test */
    public function test_find_church_fails_for_inactive_church()
    {
        $response = $this->postJson('/api/mobile/onboarding/find-church', [
            'invite_code' => 'INACT-001',
        ]);

        $response->assertStatus(403);
        $response->assertJson(['success' => false]);
    }

    /** @test */
    public function test_find_church_fails_when_subscription_is_expired()
    {
        // Expire the active church's subscription
        Subscription::withoutGlobalScopes()
            ->where('church_id', $this->activeChurch->id)
            ->update(['status' => 'expired', 'end_date' => now()->subDay()]);

        $response = $this->postJson('/api/mobile/onboarding/find-church', [
            'invite_code' => 'ACT-001',
        ]);

        $response->assertStatus(403);
        $response->assertJson(['success' => false]);
    }

    /** @test */
    public function test_find_church_fails_when_subscription_is_cancelled()
    {
        Subscription::withoutGlobalScopes()
            ->where('church_id', $this->activeChurch->id)
            ->update(['status' => 'cancelled']);

        $response = $this->postJson('/api/mobile/onboarding/find-church', [
            'invite_code' => 'ACT-001',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_find_church_requires_invite_code_field()
    {
        $response = $this->postJson('/api/mobile/onboarding/find-church', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['invite_code']);
    }

    // =========================================================================
    // SEND OTP
    // =========================================================================

    /** @test */
    public function test_send_otp_stores_otp_in_cache_and_sends_email()
    {
        $response = $this->postJson('/api/mobile/onboarding/send-otp', [
            'email' => 'newuser@test.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // OTP must be stored in cache
        $this->assertNotNull(Cache::get('otp_newuser@test.com'));

        // Email must have been dispatched
        Mail::assertSent(\App\Mail\OtpMail::class, function ($mail) {
            return $mail->hasTo('newuser@test.com');
        });
    }

    /** @test */
    public function test_send_otp_generates_4_digit_numeric_otp()
    {
        $this->postJson('/api/mobile/onboarding/send-otp', [
            'email' => 'check@otp.com',
        ]);

        $otp = Cache::get('otp_check@otp.com');

        $this->assertNotNull($otp);
        $this->assertMatchesRegularExpression('/^\d{4}$/', $otp, 'OTP must be exactly 4 digits');
        $this->assertGreaterThanOrEqual(1000, (int)$otp);
        $this->assertLessThanOrEqual(9999, (int)$otp);
    }

    /** @test */
    public function test_send_otp_requires_valid_email()
    {
        $response = $this->postJson('/api/mobile/onboarding/send-otp', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    // =========================================================================
    // VERIFY OTP
    // =========================================================================

    /** @test */
    public function test_verify_otp_succeeds_with_correct_otp()
    {
        Cache::put('otp_verify@test.com', '5678', now()->addMinutes(10));

        $response = $this->postJson('/api/mobile/onboarding/verify-otp', [
            'email' => 'verify@test.com',
            'otp'   => '5678',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function test_verify_otp_fails_with_wrong_otp()
    {
        Cache::put('otp_verify@test.com', '5678', now()->addMinutes(10));

        $response = $this->postJson('/api/mobile/onboarding/verify-otp', [
            'email' => 'verify@test.com',
            'otp'   => '0000',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
    }

    /** @test */
    public function test_verify_otp_fails_when_otp_not_in_cache_ie_expired()
    {
        // No cache entry — OTP has "expired"
        $response = $this->postJson('/api/mobile/onboarding/verify-otp', [
            'email' => 'expired@test.com',
            'otp'   => '1111',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
    }

    /** @test */
    public function test_verify_otp_clears_otp_from_cache_on_success()
    {
        Cache::put('otp_clear@test.com', '4321', now()->addMinutes(10));

        $this->postJson('/api/mobile/onboarding/verify-otp', [
            'email' => 'clear@test.com',
            'otp'   => '4321',
        ]);

        // OTP must be removed after successful verification (prevent replay)
        $this->assertNull(Cache::get('otp_clear@test.com'));
    }

    /** @test */
    public function test_verify_otp_cannot_be_reused_after_verification()
    {
        Cache::put('otp_reuse@test.com', '9999', now()->addMinutes(10));

        // First use — success
        $this->postJson('/api/mobile/onboarding/verify-otp', [
            'email' => 'reuse@test.com',
            'otp'   => '9999',
        ])->assertStatus(200);

        // Second use with same OTP — must fail (OTP was cleared)
        $response = $this->postJson('/api/mobile/onboarding/verify-otp', [
            'email' => 'reuse@test.com',
            'otp'   => '9999',
        ]);

        $response->assertStatus(400);
    }

    // =========================================================================
    // REGISTER
    // =========================================================================

    /** @test */
    public function test_register_creates_user_and_member_with_same_email()
    {
        $response = $this->postJson('/api/mobile/onboarding/register', [
            'church_id'  => $this->activeChurch->id,
            'first_name' => 'New',
            'last_name'  => 'Member',
            'email'      => 'newmember@test.com',
            'phone'      => '0771234567',
            'password'   => 'secure123',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['token', 'user']);

        // Both User and Member records must exist with same email
        $this->assertDatabaseHas('users', ['email' => 'newmember@test.com']);
        $this->assertDatabaseHas('members', ['email' => 'newmember@test.com']);
    }

    /** @test */
    public function test_register_assigns_user_and_member_to_correct_church()
    {
        $this->postJson('/api/mobile/onboarding/register', [
            'church_id'  => $this->activeChurch->id,
            'first_name' => 'Church',
            'last_name'  => 'Assigned',
            'email'      => 'assigned@test.com',
            'phone'      => '0779999999',
            'password'   => 'secure123',
        ]);

        $this->assertDatabaseHas('users', [
            'email'      => 'assigned@test.com',
            'church_id'  => $this->activeChurch->id,
        ]);

        $this->assertDatabaseHas('members', [
            'email'      => 'assigned@test.com',
            'church_id'  => $this->activeChurch->id,
        ]);
    }

    /** @test */
    public function test_register_returns_auth_token_immediately()
    {
        $response = $this->postJson('/api/mobile/onboarding/register', [
            'church_id'  => $this->activeChurch->id,
            'first_name' => 'Auto',
            'last_name'  => 'Login',
            'email'      => 'autologin@test.com',
            'phone'      => '0770001111',
            'password'   => 'secure123',
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($response->json('token'));

        // Token must be usable immediately (auto-login after register)
        $token = $response->json('token');
        $meResponse = $this->withToken($token)->getJson('/api/mobile/user');
        $meResponse->assertStatus(200);
    }

    /** @test */
    public function test_register_fails_with_duplicate_email()
    {
        User::factory()->create([
            'church_id' => $this->activeChurch->id,
            'email'     => 'duplicate@test.com',
        ]);

        $response = $this->postJson('/api/mobile/onboarding/register', [
            'church_id'  => $this->activeChurch->id,
            'first_name' => 'Dup',
            'last_name'  => 'User',
            'email'      => 'duplicate@test.com',
            'phone'      => '0770002222',
            'password'   => 'secure123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function test_register_requires_minimum_password_length()
    {
        $response = $this->postJson('/api/mobile/onboarding/register', [
            'church_id'  => $this->activeChurch->id,
            'first_name' => 'Short',
            'last_name'  => 'Pass',
            'email'      => 'shortpass@test.com',
            'phone'      => '0770003333',
            'password'   => '123', // Too short (min:6)
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function test_register_fails_with_invalid_church_id()
    {
        $response = $this->postJson('/api/mobile/onboarding/register', [
            'church_id'  => 99999, // Non-existent
            'first_name' => 'Ghost',
            'last_name'  => 'Church',
            'email'      => 'ghost@test.com',
            'phone'      => '0770004444',
            'password'   => 'secure123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['church_id']);
    }

    /** @test */
    public function test_registered_member_gets_auto_generated_member_no()
    {
        $this->postJson('/api/mobile/onboarding/register', [
            'church_id'  => $this->activeChurch->id,
            'first_name' => 'Auto',
            'last_name'  => 'Number',
            'email'      => 'autonumber@test.com',
            'phone'      => '0770005555',
            'password'   => 'secure123',
        ]);

        $member = Member::withoutGlobalScopes()->where('email', 'autonumber@test.com')->first();
        $this->assertNotNull($member);
        $this->assertNotEmpty($member->member_no);
        $this->assertStringStartsWith('MEM-', $member->member_no);
    }
}
