<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Member;
use App\Models\Church;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MemberControllerTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    protected User $userA;
    protected User $userB;
    protected Church $churchA;
    protected Church $churchB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->churchA = Church::create(['church_name' => 'Church A', 'email' => 'a@church.com', 'phone' => '1234567890', 'pastor_name' => 'Pastor A', 'registration_no' => 'REG001', 'address' => '123 St', 'city' => 'City A', 'country' => 'Country A']);
        $this->churchB = Church::create(['church_name' => 'Church B', 'email' => 'b@church.com', 'phone' => '0987654321', 'pastor_name' => 'Pastor B', 'registration_no' => 'REG002', 'address' => '456 St', 'city' => 'City B', 'country' => 'Country B']);

        $this->userA = User::factory()->create(['church_id' => $this->churchA->id]);
        $this->userB = User::factory()->create(['church_id' => $this->churchB->id]);

        $permissions = ['view_members', 'create_members', 'edit_members', 'delete_members'];
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->userA->givePermissionTo($permissions);
        $this->userB->givePermissionTo($permissions);
    }

    public function test_user_can_view_only_their_church_members()
    {
        Member::create(['first_name' => 'John', 'last_name' => 'Doe', 'church_id' => $this->churchA->id, 'member_no' => 'MBR-0001', 'gender' => 'male']);
        Member::create(['first_name' => 'Jane', 'last_name' => 'Doe', 'church_id' => $this->churchB->id, 'member_no' => 'MBR-0002', 'gender' => 'female']);

        $response = $this->actingAs($this->userA)->getJson('/api/members');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals('John', $response->json('data.0.first_name'));
    }

    public function test_user_can_export_csv_only_for_their_church()
    {
        Member::create(['first_name' => 'John', 'last_name' => 'Doe', 'church_id' => $this->churchA->id, 'member_no' => 'MBR-0001', 'gender' => 'male']);
        Member::create(['first_name' => 'Jane', 'last_name' => 'Doe', 'church_id' => $this->churchB->id, 'member_no' => 'MBR-0002', 'gender' => 'female']);

        $response = $this->actingAs($this->userA)->get('/api/members/export');

        $response->assertStatus(200);
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('John', $content);
        $this->assertStringNotContainsString('Jane', $content);
    }
}
