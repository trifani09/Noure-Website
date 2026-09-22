<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CustomerAccountApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_can_create_and_update_an_address(): void
    {
        $customer = Customer::factory()->create();
        $payload = ['label' => 'Studio', 'recipient_name' => 'Nadia Sari', 'phone' => '+6281211112222', 'line1' => '1 Main Street', 'city' => 'Jakarta', 'postal_code' => '12345', 'country_code' => 'ID', 'is_default_shipping' => true, 'is_default_billing' => true];
        $created = $this->actingAs($customer, 'customer')->postJson('/api/v1/customer/addresses', $payload)
            ->assertCreated()->assertJsonPath('data.label', 'Studio')->json('data');
        $this->actingAs($customer, 'customer')->putJson('/api/v1/customer/addresses/'.$created['public_id'], ['line1' => '2 New Street'] + $payload)
            ->assertOk()->assertJsonPath('data.line1', '2 New Street');
    }

    public function test_customer_cannot_access_another_customers_address(): void
    {
        $address = Address::factory()->create();
        $this->actingAs(Customer::factory()->create(), 'customer')->putJson('/api/v1/customer/addresses/'.$address->public_id, ['recipient_name' => 'Nadia Sari', 'phone' => '+6281211112222', 'line1' => '2 Main Street', 'city' => 'Jakarta', 'postal_code' => '12345', 'country_code' => 'ID'])->assertNotFound();
    }

    public function test_default_address_changes_are_exclusive(): void
    {
        $customer = Customer::factory()->create();
        $first = Address::factory()->for($customer)->create(['is_default_shipping' => true, 'is_default_billing' => true]);
        $second = $this->actingAs($customer, 'customer')->postJson('/api/v1/customer/addresses', ['recipient_name' => 'Nadia Sari', 'phone' => '+6281211112222', 'line1' => '2 Main Street', 'city' => 'Jakarta', 'postal_code' => '12345', 'country_code' => 'ID', 'is_default_shipping' => true])->assertCreated()->json('data');
        $this->assertFalse((bool) $first->refresh()->is_default_shipping);
        $this->assertTrue((bool) Address::where('public_id', $second['public_id'])->first()->is_default_shipping);
        $this->assertTrue((bool) $first->refresh()->is_default_billing);
    }

    public function test_customer_can_list_and_view_only_own_orders(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->for($customer)->create(['order_number' => 'NOU-MINE']);
        OrderItem::factory()->for($order)->create(['quantity' => 2]);
        $other = Order::factory()->create(['order_number' => 'NOU-OTHER']);
        $this->actingAs($customer, 'customer')->getJson('/api/v1/customer/orders')->assertOk()->assertJsonPath('data.0.order_number', 'NOU-MINE')->assertJsonPath('data.0.item_count', 2);
        $this->actingAs($customer, 'customer')->getJson('/api/v1/customer/orders/'.$order->public_id)->assertOk()->assertJsonPath('data.billing_address.country_code', 'ID');
        $this->actingAs($customer, 'customer')->getJson('/api/v1/customer/orders/'.$other->public_id)->assertNotFound();
    }

    public function test_customer_account_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/customer/profile')->assertUnauthorized();
        $this->getJson('/api/v1/customer/addresses')->assertUnauthorized();
        $this->getJson('/api/v1/customer/orders')->assertUnauthorized();
    }
}