<?php

namespace Tests\Feature\Api;

use App\Models\Church;
use App\Models\FinanceCategory;
use App\Models\FinanceExpense;
use App\Models\FinanceIncome;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * FinanceTenantTest
 *
 * Tests that ALL finance endpoints are correctly scoped to the authenticated
 * user's church. Cross-tenant data leakage must be impossible.
 *
 * Covers Audit Issue #6: Web Finance controllers may lack church scoping.
 */
class FinanceTenantTest extends TestCase
{
    use RefreshDatabase;

    protected Church $churchA;
    protected Church $churchB;
    protected Plan $plan;
    protected User $userA;
    protected User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name'             => 'Growth',
            'price'            => 29.99,
            'member_limit'     => 500,
            'user_limit'       => 10,
            'storage_limit_mb' => 1024,
            'features'         => json_encode([
                'finance' => ['bank_accounts' => true, 'budgets' => true],
                'reports' => ['enabled' => true],
            ]),
        ]);

        $this->churchA = Church::create([
            'church_name' => 'Finance Church A', 'email' => 'fa@test.com',
            'phone' => '0111111111', 'pastor_name' => 'PA',
            'registration_no' => 'FA-001', 'address' => '1 St', 'city' => 'A', 'country' => 'LK', 'status' => 'active',
        ]);

        $this->churchB = Church::create([
            'church_name' => 'Finance Church B', 'email' => 'fb@test.com',
            'phone' => '0222222222', 'pastor_name' => 'PB',
            'registration_no' => 'FB-001', 'address' => '2 St', 'city' => 'B', 'country' => 'LK', 'status' => 'active',
        ]);

        foreach ([$this->churchA->id, $this->churchB->id] as $cid) {
            Subscription::withoutGlobalScopes()->create([
                'church_id' => $cid, 'plan_id' => $this->plan->id,
                'status' => 'active', 'start_date' => now()->subMonth(), 'end_date' => now()->addMonth(), 'amount' => 29.99,
            ]);
        }

        $permissions = ['view_finance', 'create_income', 'create_expense', 'edit_finance', 'delete_finance'];
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->userA = User::factory()->create(['church_id' => $this->churchA->id]);
        $this->userB = User::factory()->create(['church_id' => $this->churchB->id]);

        $this->userA->givePermissionTo($permissions);
        $this->userB->givePermissionTo($permissions);
    }

    // =========================================================================
    // FINANCE RECORDS (LEDGER)
    // =========================================================================

    /** @test */
    public function test_finance_records_only_returns_own_church_records()
    {
        // Church A income
        FinanceIncome::withoutGlobalScopes()->create([
            'church_id' => $this->churchA->id, 'category' => 'Tithe',
            'amount' => 100, 'income_date' => now()->toDateString(), 'method' => 'Cash',
        ]);

        // Church B income — must NOT appear for userA
        FinanceIncome::withoutGlobalScopes()->create([
            'church_id' => $this->churchB->id, 'category' => 'Offering',
            'amount' => 9999, 'income_date' => now()->toDateString(), 'method' => 'Cash',
        ]);

        $response = $this->actingAs($this->userA)->getJson('/api/finance/records');

        $response->assertStatus(200);

        $amounts = collect($response->json())->pluck('amount')->map(fn($v) => (float)$v)->toArray();

        $this->assertContains(100.0, $amounts, 'Church A record must be visible');
        $this->assertNotContains(9999.0, $amounts, 'Church B record must NOT be visible to userA');
    }

    /** @test */
    public function test_cannot_delete_finance_record_from_another_church()
    {
        // Church B creates an income record
        $incomeBId = FinanceIncome::withoutGlobalScopes()->create([
            'church_id' => $this->churchB->id, 'category' => 'Secret Offering',
            'amount' => 5000, 'income_date' => now()->toDateString(), 'method' => 'Cash',
        ])->id;

        // userA tries to delete it using the composite ID format
        $response = $this->actingAs($this->userA)->deleteJson("/api/finance/records/income-{$incomeBId}");

        // Must NOT be able to delete another church's record
        $response->assertStatus(404);

        $this->assertDatabaseHas('finance_income', ['id' => $incomeBId]);
    }

    // =========================================================================
    // CATEGORIES
    // =========================================================================

    /** @test */
    public function test_finance_categories_scoped_to_own_church()
    {
        FinanceCategory::withoutGlobalScopes()->create([
            'church_id' => $this->churchA->id, 'name' => 'Alpha Tithe',
            'type' => 'income', 'status' => 'active', 'description' => 'A tithe',
        ]);

        FinanceCategory::withoutGlobalScopes()->create([
            'church_id' => $this->churchB->id, 'name' => 'Beta Secret Fund',
            'type' => 'income', 'status' => 'active', 'description' => 'B fund',
        ]);

        $response = $this->actingAs($this->userA)->getJson('/api/finance-categories');

        $response->assertStatus(200);

        $names = collect($response->json())->pluck('name')->toArray();

        $this->assertContains('Alpha Tithe', $names);
        $this->assertNotContains('Beta Secret Fund', $names);
    }

    /** @test */
    public function test_create_income_stores_with_correct_church_id()
    {
        $response = $this->actingAs($this->userA)->postJson('/api/income', [
            'category'    => 'Sunday Offering',
            'amount'      => 1500.00,
            'income_date' => now()->toDateString(),
            'method'      => 'Cash',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('finance_income', [
            'church_id' => $this->churchA->id,
            'amount'    => 1500.00,
        ]);

        // Must NOT be stored under Church B
        $this->assertDatabaseMissing('finance_income', [
            'church_id' => $this->churchB->id,
            'amount'    => 1500.00,
        ]);
    }

    /** @test */
    public function test_create_expense_stores_with_correct_church_id()
    {
        $response = $this->actingAs($this->userA)->postJson('/api/expenses', [
            'category'     => 'Utilities',
            'amount'       => 250.00,
            'expense_date' => now()->toDateString(),
            'method'       => 'Bank Transfer',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('finance_expenses', [
            'church_id' => $this->churchA->id,
            'amount'    => 250.00,
        ]);
    }

    // =========================================================================
    // INCOME VALIDATION
    // =========================================================================

    /** @test */
    public function test_income_store_requires_all_required_fields()
    {
        $response = $this->actingAs($this->userA)->postJson('/api/income', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category', 'amount', 'income_date', 'method']);
    }

    /** @test */
    public function test_income_store_rejects_invalid_payment_method()
    {
        $response = $this->actingAs($this->userA)->postJson('/api/income', [
            'category'    => 'Tithe',
            'amount'      => 100,
            'income_date' => now()->toDateString(),
            'method'      => 'Bitcoin', // Not allowed
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['method']);
    }

    /** @test */
    public function test_income_store_rejects_negative_amount()
    {
        $response = $this->actingAs($this->userA)->postJson('/api/income', [
            'category'    => 'Tithe',
            'amount'      => -100, // Negative!
            'income_date' => now()->toDateString(),
            'method'      => 'Cash',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);
    }

    // =========================================================================
    // PERMISSION CHECKS
    // =========================================================================

    /** @test */
    public function test_finance_records_require_view_finance_permission()
    {
        $userNoPerms = User::factory()->create(['church_id' => $this->churchA->id]);

        Subscription::withoutGlobalScopes()->create([
            'church_id' => $this->churchA->id, 'plan_id' => $this->plan->id,
            'status' => 'active', 'start_date' => now()->subMonth(), 'end_date' => now()->addMonth(), 'amount' => 29.99,
        ]);

        $response = $this->actingAs($userNoPerms)->getJson('/api/finance/records');

        $response->assertStatus(403);
    }

    /** @test */
    public function test_create_income_requires_create_income_permission()
    {
        $userNoPerms = User::factory()->create(['church_id' => $this->churchA->id]);

        $response = $this->actingAs($userNoPerms)->postJson('/api/income', [
            'category' => 'Tithe', 'amount' => 100,
            'income_date' => now()->toDateString(), 'method' => 'Cash',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_delete_finance_record_requires_delete_finance_permission()
    {
        $income = FinanceIncome::withoutGlobalScopes()->create([
            'church_id' => $this->churchA->id, 'category' => 'Deletable',
            'amount' => 50, 'income_date' => now()->toDateString(), 'method' => 'Cash',
        ]);

        $userNoDelete = User::factory()->create(['church_id' => $this->churchA->id]);
        $userNoDelete->givePermissionTo(['view_finance', 'create_income']);

        $response = $this->actingAs($userNoDelete)->deleteJson("/api/finance/records/income-{$income->id}");

        $response->assertStatus(403);
    }
}
