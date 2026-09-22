<?php

namespace Tests\Feature;

use App\Inventory\OrderInventoryService;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderInventoryLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    private InventoryLevel $level;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->order = Order::factory()->create(['status' => 'pending', 'payment_status' => 'unpaid', 'fulfillment_status' => 'unfulfilled']);
        $variant = ProductVariant::factory()->create();
        $location = InventoryLocation::factory()->create(['is_active' => true]);
        $this->level = InventoryLevel::factory()->for($variant, 'variant')->for($location, 'location')->create(['on_hand' => 10, 'reserved' => 2, 'safety_stock' => 0, 'version' => 1]);
        InventoryMovement::factory()->for($this->level, 'inventoryLevel')->create([
            'quantity_delta' => -2, 'movement_type' => 'reservation', 'reference_type' => 'order', 'reference_id' => $this->order->public_id,
        ]);
    }

    public function test_payment_sale_conversion_reduces_on_hand_and_reserved_once(): void
    {
        $service = app(OrderInventoryService::class);
        $service->convertReservationsToSale($this->order);
        $service->convertReservationsToSale($this->order);
        $this->assertDatabaseHas('inventory_levels', ['id' => $this->level->id, 'on_hand' => 8, 'reserved' => 0, 'version' => 2]);
        $this->assertDatabaseCount('inventory_movements', 2);
        $this->assertSame(8, $this->level->fresh()->available);
    }

    public function test_release_is_idempotent_and_does_not_reduce_on_hand(): void
    {
        $service = app(OrderInventoryService::class);
        $service->releaseReservations($this->order);
        $service->releaseReservations($this->order);
        $this->assertDatabaseHas('inventory_levels', ['id' => $this->level->id, 'on_hand' => 10, 'reserved' => 0, 'version' => 2]);
        $this->assertDatabaseCount('inventory_movements', 2);
    }

    public function test_sold_inventory_cannot_be_released_by_cancellation(): void
    {
        app(OrderInventoryService::class)->convertReservationsToSale($this->order);
        $this->actingAs($this->admin)->putJson('/api/v1/admin/orders/'.$this->order->public_id.'/status', ['status' => 'cancelled'])
            ->assertConflict()->assertJsonPath('meta.errors.0.code', 'inventory_already_sold');
        $this->assertDatabaseHas('inventory_levels', ['id' => $this->level->id, 'on_hand' => 8, 'reserved' => 0]);
    }

    public function test_expiry_command_releases_reservation_and_is_idempotent(): void
    {
        $this->order->update(['created_at' => now()->subHour()]);
        $payment = Payment::factory()->for($this->order)->create(['status' => 'pending', 'amount' => $this->order->grand_total_amount, 'currency' => $this->order->currency]);
        $this->artisan('orders:expire-reservations')->assertSuccessful();
        $this->artisan('orders:expire-reservations')->assertSuccessful();
        $this->assertDatabaseHas('orders', ['id' => $this->order->id, 'status' => 'cancelled', 'payment_status' => 'expired', 'fulfillment_status' => 'cancelled']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'expired']);
        $this->assertDatabaseHas('inventory_levels', ['id' => $this->level->id, 'on_hand' => 10, 'reserved' => 0]);
        $this->assertDatabaseCount('payment_transactions', 1);
    }

    public function test_unpaid_order_cannot_start_fulfillment(): void
    {
        $this->actingAs($this->admin)->putJson('/api/v1/admin/orders/'.$this->order->public_id.'/fulfillment', ['status' => 'processing'])
            ->assertConflict()->assertJsonPath('meta.errors.0.code', 'payment_required');
    }

    public function test_invalid_fulfillment_transition_is_rejected(): void
    {
        $this->order->update(['payment_status' => 'paid']);
        $this->actingAs($this->admin)->putJson('/api/v1/admin/orders/'.$this->order->public_id.'/fulfillment', ['status' => 'shipped'])
            ->assertConflict()->assertJsonPath('meta.errors.0.code', 'invalid_fulfillment_transition');
    }

    public function test_paid_order_can_be_fulfilled_without_double_stock_deduction(): void
    {
        app(OrderInventoryService::class)->convertReservationsToSale($this->order);
        $this->order->update(['payment_status' => 'paid']);
        foreach (['processing', 'shipped', 'fulfilled'] as $status) {
            $this->actingAs($this->admin)->putJson('/api/v1/admin/orders/'.$this->order->public_id.'/fulfillment', ['status' => $status])->assertOk();
        }
        $this->assertDatabaseHas('orders', ['id' => $this->order->id, 'status' => 'completed', 'fulfillment_status' => 'fulfilled']);
        $this->assertDatabaseHas('inventory_levels', ['id' => $this->level->id, 'on_hand' => 8, 'reserved' => 0]);
        $this->assertDatabaseCount('inventory_movements', 2);
    }
}
