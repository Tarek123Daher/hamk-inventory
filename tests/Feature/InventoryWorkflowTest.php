<?php

namespace Tests\Feature;

use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Component;
use App\Models\Kit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class InventoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_recorded_database_quantities(): void
    {
        $user = User::factory()->create(['role' => 'viewer']);
        Component::create(['name' => 'Test sensor', 'quantity' => 7, 'reorder_threshold' => 1]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('7')
            ->assertSee('Recorded units');
    }

    public function test_viewer_cannot_create_a_component(): void
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($user)
            ->post('/components', ['name' => 'Blocked item', 'reorder_threshold' => 0])
            ->assertForbidden();

        $this->assertDatabaseMissing('components', ['name' => 'Blocked item']);
    }

    public function test_manager_can_create_component_and_receive_stock(): void
    {
        $user = User::factory()->create(['role' => 'inventory_manager']);
        $category = Category::create(['name' => 'Sensors']);

        $this->actingAs($user)->post('/components', [
            'name' => 'Distance sensor',
            'sku' => 'SNS-01',
            'category_id' => $category->id,
            'reorder_threshold' => 2,
        ])->assertRedirect();

        $component = Component::where('sku', 'SNS-01')->firstOrFail();
        $this->actingAs($user)->post('/inventory/movements', [
            'component_id' => $component->id,
            'type' => 'received',
            'quantity' => 6,
            'reason' => 'Verified delivery',
        ])->assertRedirect();

        $this->assertDatabaseHas('components', ['id' => $component->id, 'quantity' => 6]);
        $this->assertDatabaseHas('stock_movements', ['component_id' => $component->id, 'type' => 'received', 'quantity' => 6]);
    }

    public function test_borrowing_availability_and_partial_return_are_consistent(): void
    {
        $user = User::factory()->create(['role' => 'inventory_manager']);
        $component = Component::create(['name' => 'Controller board', 'quantity' => 4, 'reorder_threshold' => 0]);

        $this->actingAs($user)->post('/borrowings', [
            'borrower_name' => 'Club member',
            'component_id' => $component->id,
            'quantity' => 3,
            'expected_return_at' => today()->addWeek()->toDateString(),
        ])->assertRedirect();

        $this->assertSame(1, $component->fresh()->available_quantity);

        $this->actingAs($user)->post('/borrowings', [
            'borrower_name' => 'Another member',
            'component_id' => $component->id,
            'quantity' => 2,
        ])->assertSessionHasErrors('quantity');

        $borrowing = Borrowing::with('items')->firstOrFail();
        $item = $borrowing->items->firstOrFail();
        $this->actingAs($user)->post("/borrowings/{$borrowing->id}/items/{$item->id}/return", [
            'quantity' => 1,
        ])->assertRedirect();

        $this->assertSame(2, $component->fresh()->available_quantity);
        $this->assertSame('partially_returned', $borrowing->fresh()->status);
    }

    public function test_kit_borrowing_rolls_back_when_any_component_is_unavailable(): void
    {
        $user = User::factory()->create(['role' => 'inventory_manager']);
        $available = Component::create(['name' => 'Available board', 'quantity' => 3]);
        $unavailable = Component::create(['name' => 'Unavailable motor', 'quantity' => 0]);
        $kit = Kit::create(['code' => 'KIT-TEST', 'name' => 'Test kit']);
        $kit->components()->attach([
            $available->id => ['quantity' => 1],
            $unavailable->id => ['quantity' => 1],
        ]);

        $this->actingAs($user)->post('/borrowings', [
            'borrower_name' => 'Club member',
            'kit_id' => $kit->id,
            'quantity' => 1,
        ])->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('borrowings', 0);
        $this->assertSame(3, $available->fresh()->available_quantity);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_csv_import_preview_keeps_physical_count_separate_and_is_repeatable(): void
    {
        $user = User::factory()->create(['role' => 'inventory_manager']);
        $contents = implode("\n", [
            'sku,name,recorded_quantity,physical_quantity,kit_code,kit_name,kit_quantity,kit_notes',
            'SNS-CSV,Distance sensor,5,4,KIT-CSV,Sensor kit,2,Mounted on frame',
        ]);

        $this->actingAs($user)->post('/imports/preview', [
            'file' => UploadedFile::fake()->createWithContent('inventory.csv', $contents),
        ])->assertRedirect('/imports/review');

        $this->get('/imports/review')->assertOk()->assertSee('PREVIEW')->assertSee('SNS-CSV');
        $this->post('/imports/commit')->assertRedirect('/imports');

        $component = Component::where('sku', 'SNS-CSV')->firstOrFail();
        $this->assertSame(5, $component->quantity);
        $this->assertSame(4, $component->verified_quantity);
        $this->assertSame(2, $component->kits()->firstOrFail()->pivot->quantity);
        $this->assertDatabaseHas('inventory_imports', ['inserted' => 1, 'updated' => 0, 'errors' => 0]);

        $this->post('/imports/preview', [
            'file' => UploadedFile::fake()->createWithContent('inventory.csv', $contents),
        ])->assertRedirect('/imports')->assertSessionHas('success');

        $this->assertDatabaseCount('components', 1);
        $this->assertDatabaseCount('inventory_imports', 1);
    }

    public function test_csv_import_preserves_unmapped_fields_and_reports_invalid_rows(): void
    {
        $user = User::factory()->create(['role' => 'inventory_manager']);
        $category = Category::create(['name' => 'Sensors']);
        Component::create([
            'sku' => 'SNS-OLD',
            'name' => 'Old name',
            'category_id' => $category->id,
            'manufacturer' => 'HAMK lab',
            'quantity' => 2,
            'reorder_threshold' => 3,
        ]);
        $contents = implode("\n", [
            'sku,name,recorded_quantity',
            'SNS-OLD,Updated name,4',
            'SNS-BAD,,2',
        ]);

        $this->actingAs($user)->post('/imports/preview', [
            'file' => UploadedFile::fake()->createWithContent('partial.csv', $contents),
        ])->assertRedirect('/imports/review');
        $this->post('/imports/commit')->assertRedirect('/imports')->assertSessionHas('import_summary');

        $component = Component::where('sku', 'SNS-OLD')->firstOrFail();
        $this->assertSame('Updated name', $component->name);
        $this->assertSame($category->id, $component->category_id);
        $this->assertSame('HAMK lab', $component->manufacturer);
        $this->assertSame(3, $component->reorder_threshold);
        $this->assertDatabaseHas('inventory_imports', ['inserted' => 0, 'updated' => 1, 'errors' => 1]);
    }

    public function test_first_admin_setup_creates_the_selected_account_and_login_succeeds(): void
    {
        $password = 'test-'.Str::random(30);
        $this->artisan('hamk:create-admin', [
            '--name' => 'Chosen Administrator',
            '--email' => 'owner@example.test',
        ])
            ->expectsQuestion('Choose a password (at least 12 characters)', $password)
            ->expectsQuestion('Confirm the password', $password)
            ->expectsOutput('Administrator created. Keep the password secure.')
            ->assertExitCode(0);

        $admin = User::where('email', 'owner@example.test')->firstOrFail();
        $this->assertSame('administrator', $admin->role);

        $this->post('/login', ['email' => $admin->email, 'password' => $password])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($admin);
        $this->get('/')->assertOk();
    }

    public function test_password_reset_command_updates_only_an_existing_administrator(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $password = 'reset-'.Str::random(30);

        $this->artisan('hamk:reset-admin-password', ['email' => $admin->email])
            ->expectsQuestion('Choose a new password (at least 12 characters)', $password)
            ->expectsQuestion('Confirm the new password', $password)
            ->expectsOutput('Administrator password reset. The new password was not displayed.')
            ->assertExitCode(0);

        $this->assertTrue(password_verify($password, $admin->fresh()->password));
        $this->post('/login', ['email' => $admin->email, 'password' => $password])->assertRedirect('/');
    }
}
