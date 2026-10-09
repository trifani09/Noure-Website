<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\StoreSetting;
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

        $this->actingAs($this->admin)->putJson('/api/v1/admin/settings', ['store_name' => 'Noure Indonesia', 'announcement_text' => 'Gratis ongkir akhir pekan', 'announcement_url' => '/products', 'announcement_is_active' => true, 'support_email' => 'hello@noure.test', 'support_phone' => null, 'whatsapp_number' => null, 'instagram_url' => 'https://instagram.com/noure', 'default_currency' => 'IDR', 'timezone' => 'Asia/Jakarta', 'low_stock_threshold' => 8, 'order_prefix' => 'NOU'])->assertOk()->assertJsonPath('data.low_stock_threshold', 8)->assertJsonPath('data.announcement_text', 'Gratis ongkir akhir pekan');
        $this->assertDatabaseHas('store_settings', ['store_name' => 'Noure Indonesia', 'announcement_is_active' => true]);
    }

    public function test_dashboard_returns_sales_orders_and_customer_metrics(): void
    {
        Customer::factory()->create();
        Order::factory()->create(['payment_status' => 'paid', 'status' => 'processing', 'grand_total_amount' => 325000, 'placed_at' => now()]);
        Order::factory()->create(['payment_status' => 'pending', 'status' => 'pending']);

        $this->actingAs($this->admin)->getJson('/api/v1/admin/dashboard')->assertOk()->assertJsonPath('data.sales.today', 325000)->assertJsonPath('data.pending_payment', 1)->assertJsonPath('data.orders_by_status.processing', 1)->assertJsonPath('data.new_customers.today', 1);
    }

    public function test_storefront_settings_are_public_and_exclude_internal_values(): void
    {
        StoreSetting::query()->create([
            'store_name' => 'Noure Indonesia',
            'announcement_text' => 'Belanja eksklusif di website',
            'announcement_url' => '/products?sort=newest',
            'announcement_is_active' => true,
            'support_email' => 'hello@noure.test',
            'default_currency' => 'IDR',
            'low_stock_threshold' => 8,
            'order_prefix' => 'NOU',
        ]);

        $this->getJson('/api/v1/storefront/settings')->assertOk()
            ->assertJsonPath('data.store_name', 'Noure Indonesia')
            ->assertJsonPath('data.support_email', 'hello@noure.test')
            ->assertJsonPath('data.announcement_text', 'Belanja eksklusif di website')
            ->assertJsonPath('data.announcement_url', '/products?sort=newest')
            ->assertJsonPath('data.announcement_is_active', true)
            ->assertJsonMissingPath('data.low_stock_threshold')
            ->assertJsonMissingPath('data.order_prefix');
    }

    public function test_admin_operations_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertUnauthorized();
        $this->getJson('/api/v1/admin/customers')->assertUnauthorized();
        $this->getJson('/api/v1/admin/discounts')->assertUnauthorized();
        $this->getJson('/api/v1/admin/settings')->assertUnauthorized();
    }
}
