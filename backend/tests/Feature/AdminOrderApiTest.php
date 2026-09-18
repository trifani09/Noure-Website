<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $customer = Customer::factory()->create(['first_name' => 'Nadia', 'last_name' => 'Putri', 'email' => 'nadia@example.com']);
        $this->order = Order::factory()->for($customer)->create([
            'order_number' => 'NOU-20260918-TEST', 'status' => 'pending', 'payment_status' => 'unpaid',
            'grand_total_amount' => 300000, 'subtotal_amount' => 300000, 'shipping_amount' => 0,
        ]);
        OrderItem::factory()->for($this->order)->create(['product_name' => 'Luna Dress', 'sku' => 'NOU-LUNA-M', 'quantity' => 2, 'unit_price_amount' => 150000, 'total_amount' => 300000]);
    }

    public function test_admin_can_list_search_filter_sort_and_paginate_orders(): void
    {
        Order::factory()->create(['order_number' => 'NOU-OTHER', 'status' => 'completed']);
        $this->actingAs($this->admin)->getJson('/api/v1/admin/orders?search=TEST&status=pending&payment_status=unpaid&sort=total_desc&per_page=1')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.order_number', 'NOU-20260918-TEST')
            ->assertJsonPath('data.0.customer.name', 'Nadia Putri')->assertJsonPath('meta.pagination.total', 1);
    }

    public function test_admin_can_view_order_detail(): void
    {
        $this->actingAs($this->admin)->getJson('/api/v1/admin/orders/'.$this->order->public_id)
            ->assertOk()->assertJsonPath('data.items.0.product_name', 'Luna Dress')
            ->assertJsonPath('data.shipping_address.country_code', 'ID')->assertJsonPath('data.payment.provider', null)
            ->assertJsonPath('data.status_history.0.to', 'pending');
    }

    public function test_admin_can_update_status_and_history_is_preserved(): void
    {
        $this->actingAs($this->admin)->putJson('/api/v1/admin/orders/'.$this->order->public_id.'/status', ['status' => 'processing'])
            ->assertOk()->assertJsonPath('data.status', 'processing')->assertJsonPath('data.status_history.1.from', 'pending')
            ->assertJsonPath('data.status_history.1.to', 'processing');
        $this->assertDatabaseHas('orders', ['id' => $this->order->id, 'status' => 'processing']);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $this->order->update(['status' => 'shipped']);
        $this->actingAs($this->admin)->putJson('/api/v1/admin/orders/'.$this->order->public_id.'/status', ['status' => 'processing'])
            ->assertConflict()->assertJsonPath('meta.errors.0.code', 'invalid_status_transition');
        $this->assertDatabaseHas('orders', ['id' => $this->order->id, 'status' => 'shipped']);
    }

    public function test_cancelling_order_releases_reserved_inventory(): void
    {
        $variant = ProductVariant::factory()->create();
        $location = InventoryLocation::factory()->create(['is_active' => true]);
        $level = InventoryLevel::factory()->for($variant, 'variant')->for($location, 'location')->create(['on_hand' => 10, 'reserved' => 2, 'version' => 1]);
        InventoryMovement::factory()->for($level, 'inventoryLevel')->create([
            'quantity_delta' => -2, 'movement_type' => 'reservation', 'reference_type' => 'order', 'reference_id' => $this->order->public_id,
        ]);

        $this->actingAs($this->admin)->putJson('/api/v1/admin/orders/'.$this->order->public_id.'/status', ['status' => 'cancelled'])
            ->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertDatabaseHas('inventory_levels', ['id' => $level->id, 'on_hand' => 10, 'reserved' => 0, 'version' => 2]);
        $this->assertDatabaseHas('inventory_movements', ['inventory_level_id' => $level->id, 'movement_type' => 'release', 'quantity_delta' => 2, 'actor_id' => $this->admin->id]);
    }

    public function test_order_management_requires_admin_authentication(): void
    {
        $this->getJson('/api/v1/admin/orders')->assertUnauthorized();
        $this->getJson('/api/v1/admin/orders/'.$this->order->public_id)->assertUnauthorized();
        $this->putJson('/api/v1/admin/orders/'.$this->order->public_id.'/status', ['status' => 'processing'])->assertUnauthorized();
    }
}
