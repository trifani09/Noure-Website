<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOperationsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_admin_can_manage_customers_discounts_and_settings(): void
    {
        $customer = Customer::factory()->create(['first_name' => 'Nadia', 'status' => 'active']);
        $this->actingAs($this->admin)->getJson('/api/v1/admin/customers?search=Nadia')->assertOk()->assertJsonPath('data.0.public_id', $customer->public_id);
        $this->actingAs($this->admin)->putJson("/api/v1/admin/customers/{$customer->public_id}/status", ['status' => 'disabled'])->assertOk()->assertJsonPath('data.status', 'disabled');

        $discount = $this->actingAs($this->admin)->postJson('/api/v1/admin/discounts', ['code' => 'welcome10', 'name' => 'Welcome', 'type' => 'percentage', 'value' => 1000, 'is_active' => true])->assertCreated()->assertJsonPath('data.code', 'WELCOME10')->json('data');
        $this->actingAs($this->admin)->putJson("/api/v1/admin/discounts/{$discount['public_id']}", ['name' => 'Welcome updated'])->assertOk()->assertJsonPath('data.name', 'Welcome updated');

        $this->actingAs($this->admin)->putJson('/api/v1/admin/settings', ['store_name' => 'Noure Indonesia', 'support_email' => 'hello@noure.test', 'support_phone' => null, 'whatsapp_number' => null, 'instagram_url' => 'https://instagram.com/noure', 'default_currency' => 'IDR', 'timezone' => 'Asia/Jakarta', 'low_stock_threshold' => 8, 'order_prefix' => 'NOU'])->assertOk()->assertJsonPath('data.low_stock_threshold', 8);
        $this->assertDatabaseHas('store_settings', ['store_name' => 'Noure Indonesia']);
    }

    public function test_dashboard_returns_sales_orders_and_customer_metrics(): void
    {
        Customer::factory()->create();
        Order::factory()->create(['payment_status' => 'paid', 'status' => 'processing', 'grand_total_amount' => 325000, 'placed_at' => now()]);
        Order::factory()->create(['payment_status' => 'pending', 'status' => 'pending']);

        $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard')->assertOk()->assertJsonPath('data.sales.today', 325000)->assertJsonPath('data.pending_payment', 1)->assertJsonPath('data.orders_by_status.processing', 1)->assertJsonPath('data.new_customers.today', 1);
    }

    public function test_admin_operations_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertUnauthorized();
        $this->getJson('/api/v1/admin/customers')->assertUnauthorized();
        $this->getJson('/api/v1/admin/discounts')->assertUnauthorized();
        $this->getJson('/api/v1/admin/settings')->assertUnauthorized();
    }
}
