<?php

namespace Tests\Feature;

use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInventoryApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ProductVariant $variant;

    private InventoryLocation $location;

    private InventoryLevel $level;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->variant = ProductVariant::factory()->create();
        $this->location = InventoryLocation::factory()->create(['code' => 'MAIN', 'name' => 'Main warehouse', 'is_active' => true]);
        $this->level = InventoryLevel::factory()->create(['variant_id' => $this->variant->id, 'location_id' => $this->location->id, 'on_hand' => 20, 'reserved' => 5, 'safety_stock' => 2, 'version' => 3]);
    }

    public function test_admin_can_view_variant_inventory_with_derived_available_quantity(): void
    {
        $this->actingAs($this->admin)->getJson("/api/v1/admin/variants/{$this->variant->public_id}/inventory")
            ->assertOk()->assertJsonPath('data.0.location.code', 'MAIN')->assertJsonPath('data.0.on_hand', 20)
            ->assertJsonPath('data.0.available', 13)->assertJsonPath('data.0.version', 3)->assertJsonMissingPath('data.0.id');
    }

    public function test_adjustment_updates_level_and_creates_an_append_only_movement(): void
    {
        $response = $this->actingAs($this->admin)->postJson("/api/v1/admin/variants/{$this->variant->public_id}/inventory/adjustments", $this->adjustment())
            ->assertCreated()->assertJsonPath('data.level.on_hand', 30)->assertJsonPath('data.level.available', 23)
            ->assertJsonPath('data.level.version', 4)->assertJsonPath('data.movement.movement_type', 'adjustment')
            ->assertJsonPath('data.movement.reference_id', 'PO-2026-0916');

        $this->assertDatabaseHas('inventory_levels', ['id' => $this->level->id, 'on_hand' => 30, 'version' => 4]);
        $this->assertDatabaseHas('inventory_movements', ['inventory_level_id' => $this->level->id, 'quantity_delta' => 10, 'actor_id' => $this->admin->id]);
        $this->assertSame($this->admin->email, $response->json('data.movement.actor.email'));
    }

    public function test_negative_stock_is_rejected_without_creating_a_movement(): void
    {
        $payload = $this->adjustment();
        $payload['quantity_delta'] = -14;

        $this->actingAs($this->admin)->postJson("/api/v1/admin/variants/{$this->variant->public_id}/inventory/adjustments", $payload)
            ->assertConflict()->assertJsonPath('meta.errors.0.code', 'insufficient_stock');
        $this->assertDatabaseHas('inventory_levels', ['id' => $this->level->id, 'on_hand' => 20, 'version' => 3]);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_stale_version_is_rejected(): void
    {
        $payload = $this->adjustment();
        $payload['expected_version'] = 2;

        $this->actingAs($this->admin)->postJson("/api/v1/admin/variants/{$this->variant->public_id}/inventory/adjustments", $payload)
            ->assertConflict()->assertJsonPath('meta.errors.0.code', 'inventory_version_conflict');
    }

    public function test_invalid_location_and_zero_quantity_are_rejected(): void
    {
        $invalidLocation = $this->adjustment();
        $invalidLocation['location_code'] = 'UNKNOWN';
        $this->actingAs($this->admin)->postJson("/api/v1/admin/variants/{$this->variant->public_id}/inventory/adjustments", $invalidLocation)
            ->assertConflict()->assertJsonPath('meta.errors.0.code', 'location_not_found');
        $zero = $this->adjustment();
        $zero['quantity_delta'] = 0;
        $this->postJson("/api/v1/admin/variants/{$this->variant->public_id}/inventory/adjustments", $zero)->assertUnprocessable();
    }

    public function test_admin_can_paginate_and_sort_movement_history(): void
    {
        InventoryMovement::factory()->create(['inventory_level_id' => $this->level->id, 'movement_type' => 'adjustment', 'quantity_delta' => 2, 'created_at' => now()->subHour()]);
        InventoryMovement::factory()->create(['inventory_level_id' => $this->level->id, 'movement_type' => 'adjustment', 'quantity_delta' => 3, 'created_at' => now()]);

        $this->actingAs($this->admin)->getJson("/api/v1/admin/variants/{$this->variant->public_id}/inventory/movements?sort=oldest&per_page=1")
            ->assertOk()->assertJsonPath('data.0.quantity_delta', 2)->assertJsonPath('meta.pagination.total', 2)->assertJsonPath('meta.pagination.last_page', 2);
    }

    public function test_inventory_endpoints_require_authentication(): void
    {
        $this->getJson("/api/v1/admin/variants/{$this->variant->public_id}/inventory")->assertUnauthorized();
        $this->postJson("/api/v1/admin/variants/{$this->variant->public_id}/inventory/adjustments", $this->adjustment())->assertUnauthorized();
        $this->getJson("/api/v1/admin/variants/{$this->variant->public_id}/inventory/movements")->assertUnauthorized();
    }

    public function test_movements_have_no_edit_or_delete_routes(): void
    {
        $movement = InventoryMovement::factory()->create(['inventory_level_id' => $this->level->id]);
        $this->actingAs($this->admin)->putJson("/api/v1/admin/variants/{$this->variant->public_id}/inventory/movements/{$movement->id}", [])->assertNotFound();
        $this->deleteJson("/api/v1/admin/variants/{$this->variant->public_id}/inventory/movements/{$movement->id}")->assertNotFound();
    }

    private function adjustment(): array
    {
        return ['location_code' => 'MAIN', 'quantity_delta' => 10, 'reason' => 'Purchase order received', 'reference_type' => 'purchase_order', 'reference_id' => 'PO-2026-0916', 'expected_version' => 3];
    }
}
