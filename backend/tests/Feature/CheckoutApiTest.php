<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    private ProductVariant $variant;

    private InventoryLevel $level;

    private InventoryLocation $location;

    protected function setUp(): void
    {
        parent::setUp();
        $product = Product::factory()->create(['name' => 'Luna Dress', 'status' => 'active', 'published_at' => now()]);
        $this->variant = ProductVariant::factory()->for($product)->create([
            'sku' => 'NOU-LUNA-M', 'title' => 'Cream / M', 'is_active' => true, 'price_amount' => 150000, 'currency' => 'IDR',
        ]);
        $this->location = InventoryLocation::factory()->create(['is_active' => true]);
        $this->level = InventoryLevel::factory()->for($this->variant, 'variant')->for($this->location, 'location')->create([
            'on_hand' => 10, 'reserved' => 1, 'safety_stock' => 1,
        ]);
    }

    public function test_guest_checkout_returns_summary_and_creates_order(): void
    {
        [$token] = $this->guestCart(2);
        $this->withUnencryptedCookie('noure_cart', $token)->getJson('/api/v1/checkout')
            ->assertOk()->assertJsonPath('data.cart.item_count', 2)->assertJsonPath('data.totals.grand_total_amount', 300000);

        $response = $this->withUnencryptedCookie('noure_cart', $token)->postJson('/api/v1/orders', $this->guestPayload())
            ->assertCreated()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.payment_status', 'unpaid')
            ->assertJsonPath('data.fulfillment_status', 'unfulfilled')->assertJsonPath('data.grand_total_amount', 300000);

        $this->assertDatabaseHas('orders', ['order_number' => $response->json('data.order_number'), 'customer_id' => null, 'grand_total_amount' => 300000]);
        $this->assertDatabaseHas('order_items', ['product_name' => 'Luna Dress', 'sku' => 'NOU-LUNA-M', 'quantity' => 2, 'total_amount' => 300000]);
        $this->assertDatabaseHas('carts', ['status' => 'converted']);
        $this->assertDatabaseHas('inventory_levels', ['id' => $this->level->id, 'reserved' => 3]);
        $this->assertDatabaseHas('inventory_movements', ['movement_type' => 'reservation', 'quantity_delta' => -2]);
    }

    public function test_authenticated_checkout_uses_customer_and_saved_address(): void
    {
        $customer = Customer::factory()->create(['email' => 'customer@example.com', 'phone' => '08123456789']);
        $address = Address::factory()->for($customer)->create(['line1' => 'Jl. Melati 10', 'city' => 'Jakarta']);
        $cart = Cart::factory()->for($customer)->create(['status' => 'active', 'currency' => 'IDR']);
        CartItem::factory()->for($cart)->for($this->variant, 'variant')->create(['quantity' => 1, 'unit_price_snapshot' => 150000, 'currency' => 'IDR']);

        $this->actingAs($customer, 'customer')->getJson('/api/v1/checkout')->assertOk()
            ->assertJsonPath('data.customer.email', 'customer@example.com')->assertJsonPath('data.addresses.0.public_id', $address->public_id);
        $this->actingAs($customer, 'customer')->postJson('/api/v1/orders', ['address_public_id' => $address->public_id])
            ->assertCreated()->assertJsonPath('data.email', 'customer@example.com')->assertJsonPath('data.shipping_address.line1', 'Jl. Melati 10');
        $this->assertDatabaseHas('orders', ['customer_id' => $customer->id, 'email' => 'customer@example.com']);
    }

    public function test_empty_cart_is_rejected(): void
    {
        [$token] = $this->guestCart(0);
        $this->withUnencryptedCookie('noure_cart', $token)->postJson('/api/v1/orders', $this->guestPayload())
            ->assertUnprocessable()->assertJsonPath('meta.errors.0.code', 'empty_cart');
    }

    public function test_unavailable_stock_is_rejected(): void
    {
        [$token] = $this->guestCart(2);
        $this->level->update(['on_hand' => 2, 'reserved' => 1, 'safety_stock' => 0]);
        $this->withUnencryptedCookie('noure_cart', $token)->postJson('/api/v1/orders', $this->guestPayload())
            ->assertConflict()->assertJsonPath('meta.errors.0.code', 'insufficient_stock');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_price_change_is_rejected(): void
    {
        [$token] = $this->guestCart(1);
        $this->variant->update(['price_amount' => 175000]);
        $this->withUnencryptedCookie('noure_cart', $token)->postJson('/api/v1/orders', $this->guestPayload())
            ->assertConflict()->assertJsonPath('meta.errors.0.code', 'price_changed');
    }

    public function test_transaction_rolls_back_order_items_and_reservations(): void
    {
        [$token, $cart] = $this->guestCart(1);
        $secondVariant = ProductVariant::factory()->for(Product::factory()->create())->create(['is_active' => true, 'price_amount' => 90000, 'currency' => 'IDR']);
        InventoryLevel::factory()->for($secondVariant, 'variant')->for($this->location, 'location')->create(['on_hand' => 0, 'reserved' => 0, 'safety_stock' => 0]);
        CartItem::factory()->for($cart)->for($secondVariant, 'variant')->create(['quantity' => 1, 'unit_price_snapshot' => 90000, 'currency' => 'IDR']);

        $this->withUnencryptedCookie('noure_cart', $token)->postJson('/api/v1/orders', $this->guestPayload())->assertConflict();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseHas('inventory_levels', ['id' => $this->level->id, 'reserved' => 1]);
        $this->assertDatabaseHas('carts', ['id' => $cart->id, 'status' => 'active']);
    }

    private function guestCart(int $quantity): array
    {
        $token = Str::random(64);
        $cart = Cart::factory()->create(['customer_id' => null, 'guest_token_hash' => hash('sha256', $token), 'status' => 'active', 'currency' => 'IDR', 'expires_at' => now()->addDay()]);
        if ($quantity > 0) {
            CartItem::factory()->for($cart)->for($this->variant, 'variant')->create(['quantity' => $quantity, 'unit_price_snapshot' => 150000, 'currency' => 'IDR']);
        }

        return [$token, $cart];
    }

    /** @return array<string, mixed> */
    private function guestPayload(): array
    {
        return [
            'name' => 'Nadia Putri', 'email' => 'nadia@example.com', 'phone' => '08123456789',
            'shipping_address' => ['line1' => 'Jl. Mawar 12', 'city' => 'Bandung', 'postal_code' => '40123', 'country_code' => 'ID'],
        ];
    }
}
