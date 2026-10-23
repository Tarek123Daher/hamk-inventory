<?php

namespace Tests\Feature;

use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Component;
use App\Models\Kit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoachAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_can_manage_inventory_kits_borrowings_and_reports(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $component = Component::create([
            'name' => 'Test control board',
            'sku' => 'BOARD-01',
            'quantity' => 8,
            'reorder_threshold' => 1,
        ]);

        $this->actingAs($coach);
        $this->get('/')->assertOk();
        $this->get('/components/create')->assertOk();
        $this->get("/components/{$component->id}/edit")->assertOk();
        $this->get('/inventory')->assertOk();
        $this->get('/borrowings')->assertOk();
        $this->get('/borrowings/create')->assertOk();
        $this->get('/kits')->assertOk();
        $this->get('/reports')->assertOk();
        $this->get('/reports/export')->assertOk();
        $this->get('/imports')->assertOk();
        $this->get('/settings')->assertOk();

        $this->post('/components', [
            'name' => 'Coach-added sensor',
            'sku' => 'SENSOR-COACH',
            'reorder_threshold' => 1,
            'attributes' => [['name' => 'Voltage', 'value' => '5V']],
        ])->assertRedirect();
        $component->refresh();
        $this->put("/components/{$component->id}", [
            'name' => 'Updated control board',
            'sku' => 'BOARD-01',
            'reorder_threshold' => 1,
            'attributes' => [['name' => 'Voltage', 'value' => '3.3V']],
        ])->assertRedirect();

        $this->post('/categories', ['name' => 'Coach category'])->assertRedirect();
        $this->post('/inventory/movements', [
            'component_id' => $component->id,
            'type' => 'received',
            'quantity' => 2,
            'reason' => 'Coach receipt',
        ])->assertRedirect();
        $this->assertSame(10, $component->fresh()->quantity);

        $this->post('/kits', ['code' => 'KIT-COACH', 'name' => 'Coach kit'])->assertRedirect();
        $kit = Kit::where('code', 'KIT-COACH')->firstOrFail();
        $this->put("/kits/{$kit->id}/components", [
            'components' => [$component->id => ['quantity' => 2, 'notes' => 'Coach assigned']],
        ])->assertRedirect();

        $this->post('/borrowings', [
            'borrower_name' => 'Student',
            'component_id' => $component->id,
            'quantity' => 2,
        ])->assertRedirect();
        $borrowing = Borrowing::with('items')->firstOrFail();
        $item = $borrowing->items->firstOrFail();
        $this->post("/borrowings/{$borrowing->id}/items/{$item->id}/return", ['quantity' => 1])
            ->assertRedirect();

        $this->assertSame(1, $item->fresh()->returned_quantity);
    }

    public function test_coach_cannot_access_administrator_user_management_or_archive_components(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $target = User::factory()->create(['role' => 'viewer']);
        $component = Component::create(['name' => 'Protected component', 'quantity' => 2]);

        $this->actingAs($coach)
            ->get('/users')
            ->assertForbidden();
        $this->post('/users', [
            'name' => 'Unauthorized',
            'email' => 'unauthorized@example.test',
            'password' => 'a-long-test-password',
            'password_confirmation' => 'a-long-test-password',
            'role' => 'administrator',
        ])->assertForbidden();
        $this->patch("/users/{$target->id}/role", ['role' => 'administrator'])->assertForbidden();
        $this->put("/users/{$target->id}/password", [
            'password' => 'another-long-test-password',
            'password_confirmation' => 'another-long-test-password',
        ])->assertForbidden();
        $this->delete("/components/{$component->id}")->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'unauthorized@example.test']);
        $this->assertSame('viewer', $target->fresh()->role);
        $this->assertTrue($component->fresh()->is_active);
    }

    public function test_only_administrator_can_create_and_reset_coach_accounts(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);
        $target = User::factory()->create(['role' => 'coach']);
        $originalHash = $target->password;

        $this->actingAs($administrator)
            ->post('/users', [
                'name' => 'New coach',
                'email' => 'new.coach@example.test',
                'password' => 'a-secure-test-password',
                'password_confirmation' => 'a-secure-test-password',
                'role' => 'coach',
            ])
            ->assertRedirect();
        $newCoach = User::where('email', 'new.coach@example.test')->firstOrFail();
        $this->assertSame('coach', $newCoach->role);

        $this->put("/users/{$target->id}/password", [
            'password' => 'a-different-test-password',
            'password_confirmation' => 'a-different-test-password',
        ])->assertRedirect();
        $this->assertNotSame($originalHash, $target->fresh()->password);

        $this->assertDatabaseHas('users', ['id' => $administrator->id, 'role' => 'administrator']);
    }

    public function test_administrator_role_cannot_be_removed_from_the_last_administrator(): void
    {
        $administrator = User::factory()->create(['role' => 'administrator']);

        $this->actingAs($administrator)
            ->patch("/users/{$administrator->id}/role", ['role' => 'coach'])
            ->assertStatus(422);

        $this->assertSame('administrator', $administrator->fresh()->role);
    }
}
