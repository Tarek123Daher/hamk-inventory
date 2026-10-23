<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Kit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_browse_component_details_and_kit_contents(): void
    {
        $viewer = User::factory()->create(['role' => 'viewer']);
        $component = Component::create([
            'name' => 'Ultrasonic Sensor',
            'sku' => 'SENSOR-01',
            'manufacturer' => 'HAMK Lab',
            'model' => 'HC-SR04',
            'image_path' => 'components/sensor.png',
            'quantity' => 8,
            'damaged_quantity' => 1,
            'lost_quantity' => 1,
            'attributes' => [
                ['name' => 'Operating voltage', 'value' => '5V'],
            ],
        ]);
        $kit = Kit::create(['code' => 'KIT-01', 'name' => 'Robotics starter kit']);
        $kit->components()->attach($component->id, ['quantity' => 2]);

        $this->actingAs($viewer)
            ->get('/student-portal')
            ->assertOk()
            ->assertSee('Student inventory portal')
            ->assertSee('Ultrasonic Sensor')
            ->assertSee('HC-SR04')
            ->assertSee('Available stock')
            ->assertSee('6')
            ->assertSee('Operating voltage')
            ->assertSee('KIT-01')
            ->assertSee('× 2')
            ->assertDontSee('Add component');
    }

    public function test_student_portal_requires_authentication_and_viewers_cannot_modify_inventory(): void
    {
        $this->get('/student-portal')->assertRedirect('/login');

        $viewer = User::factory()->create(['role' => 'viewer']);
        $this->actingAs($viewer)
            ->get('/student-portal')
            ->assertOk();

        $this->post('/components', ['name' => 'Unauthorized component', 'reorder_threshold' => 0])
            ->assertForbidden();
        $this->post('/categories', ['name' => 'Unauthorized category'])
            ->assertForbidden();
        $this->post('/inventory/movements', [])
            ->assertForbidden();
        $this->post('/borrowings', [])
            ->assertForbidden();
        $this->post('/kits', [])
            ->assertForbidden();
        $this->assertDatabaseMissing('components', ['name' => 'Unauthorized component']);
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertDatabaseCount('borrowings', 0);
        $this->assertDatabaseCount('kits', 0);
    }
}
