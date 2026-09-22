<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShippingApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        config(['shipping.free_shipping_min_order' => 500000, 'shipping.weight_surcharge_per_step' => 5000]);
        $product = Product::factory()->create(['status' => 'active', 'published_at' => now()]);
        $this->variant = ProductVariant::factory()->for($product)->create(['price_amount' => 150000, 'currency' => 'IDR', 'weight_grams' => 1200]);
        $location = InventoryLocation::factory()->create(['is_active' => true]);
        InventoryLevel::factory()->for($this->variant, 'variant')->for($location, 'location')->create(['on_hand' => 20, 'reserved' => 0, 'safety_stock' => 0]);
    }

    public function test_shipping_methods_select_the_jabodetabek_zone(): void
    {
        [$token] = $this->cart(1);
        $this->withUnencryptedCookie('noure_cart', $token)->withCredentials()->getJson('/api/v1/checkout/shipping-methods?country_code=ID&city=Jakarta&province=DKI%20Jakarta')
            ->assertOk()->assertJsonPath('data.0.code', 'standard')->assertJsonPath('data.0.zone.code', 'jabodetabek')
            ->assertJsonPath('data.0.amount', 30000)->assertJsonPath('data.1.amount', 50000);
    }

    public function test_weight_and_free_shipping_are_calculated_from_the_cart(): void
    {
        [$token] = $this->cart(4);
        $this->withUnencryptedCookie('noure_cart', $token)->withCredentials()->getJson('/api/v1/checkout/shipping-methods?country_code=ID&city=Bandung&province=Jawa%20Barat')
            ->assertOk()->assertJsonPath('data.0.weight_grams', 4800)->assertJsonPath('data.0.amount', 0)->assertJsonPath('data.0.free_shipping', true);
    }

    public function test_unsupported_destination_and_inactive_method_are_rejected(): void
    {
        [$token] = $this->cart(1);
        $this->withUnencryptedCookie('noure_cart', $token)->withCredentials()->getJson('/api/v1/checkout/shipping-methods?country_code=US&city=New%20York')
            ->assertUnprocessable()->assertJsonPath('meta.errors.0.code', 'unsupported_destination');
        config(['shipping.methods.express.active' => false]);
        $this->withUnencryptedCookie('noure_cart', $token)->withCredentials()->postJson('/api/v1/orders', $this->orderPayload('express'))
            ->assertUnprocessable()->assertJsonPath('meta.errors.0.code', 'shipping_method_unavailable');
    }

    public function test_order_recalculates_shipping_and_ignores_frontend_amount(): void
    {
        [$token] = $this->cart(1);
        $response = $this->withUnencryptedCookie('noure_cart', $token)->withCredentials()->postJson('/api/v1/orders', $this->orderPayload('express') + ['shipping_amount' => 1, 'grand_total_amount' => 1])
            ->assertCreated()->assertJsonPath('data.shipping_amount', 50000)->assertJsonPath('data.grand_total_amount', 200000)
            ->assertJsonPath('data.shipping_method.code', 'express');
        $this->assertDatabaseHas('orders', ['order_number' => $response->json('data.order_number'), 'shipping_amount' => 50000, 'grand_total_amount' => 200000]);
    }

    private function cart(int $quantity): array
    {
        $token = Str::random(64);
        $cart = Cart::factory()->create(['guest_token_hash' => hash('sha256', $token), 'status' => 'active', 'currency' => 'IDR']);
        CartItem::factory()->for($cart)->for($this->variant, 'variant')->create(['quantity' => $quantity, 'unit_price_snapshot' => 150000, 'currency' => 'IDR']);

        return [$token, $cart];
    }

    /** @return array<string, mixed> */
    private function orderPayload(string $method): array
    {
        return ['name' => 'Nadia Putri', 'email' => 'nadia@example.com', 'phone' => '08123456789', 'shipping_method_code' => $method, 'shipping_address' => ['line1' => 'Jl. Mawar 12', 'city' => 'Bandung', 'province' => 'Jawa Barat', 'postal_code' => '40123', 'country_code' => 'ID']];
    }
}
