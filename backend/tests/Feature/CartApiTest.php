<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $product = Product::factory()->create(['status' => 'active', 'published_at' => now()]);
        $this->variant = ProductVariant::factory()->for($product)->create(['is_active' => true, 'price_amount' => 150000, 'currency' => 'IDR']);
        $location = InventoryLocation::factory()->create(['is_active' => true]);
        InventoryLevel::factory()->for($this->variant, 'variant')->for($location, 'location')->create(['on_hand' => 10, 'reserved' => 1, 'safety_stock' => 1]);
    }

    public function test_guest_cart_is_created_with_http_only_cookie(): void
    {
        $response = $this->getJson('/api/v1/cart')->assertOk()->assertJsonPath('data.item_count', 0);
        $cookie = collect($response->headers->getCookies())->firstWhere(fn ($cookie) => $cookie->getName() === 'noure_cart');
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame(64, strlen(Cart::query()->sole()->guest_token_hash));
    }

    public function test_guest_can_add_item_with_server_price(): void
    {
        $this->postJson('/api/v1/cart/items', ['variant_public_id' => $this->variant->public_id, 'quantity' => 2])
            ->assertCreated()->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items.0.unit_price_amount', 150000)
            ->assertJsonPath('data.subtotal_amount', 300000);
    }

    public function test_duplicate_variant_increases_quantity(): void
    {
        [$cookieName, $token] = $this->addGuestItem(1);
        $this->withUnencryptedCookie($cookieName, $token)->withCredentials()->postJson('/api/v1/cart/items', ['variant_public_id' => $this->variant->public_id, 'quantity' => 2])
            ->assertCreated()->assertJsonPath('data.items.0.quantity', 3)->assertJsonCount(1, 'data.items');
    }

    public function test_guest_can_update_quantity(): void
    {
        [$cookieName, $token, $itemId] = $this->addGuestItem(1);
        $this->withUnencryptedCookie($cookieName, $token)->withCredentials()->putJson('/api/v1/cart/items/'.$itemId, ['quantity' => 4])
            ->assertOk()->assertJsonPath('data.items.0.quantity', 4)->assertJsonPath('data.total_amount', 600000);
    }

    public function test_guest_can_remove_item(): void
    {
        [$cookieName, $token, $itemId] = $this->addGuestItem(1);
        $this->withUnencryptedCookie($cookieName, $token)->withCredentials()->deleteJson('/api/v1/cart/items/'.$itemId)->assertNoContent();
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_unavailable_variant_is_rejected(): void
    {
        InventoryLevel::query()->update(['on_hand' => 2, 'reserved' => 1, 'safety_stock' => 1]);
        $this->postJson('/api/v1/cart/items', ['variant_public_id' => $this->variant->public_id, 'quantity' => 1])
            ->assertConflict()->assertJsonPath('meta.errors.0.code', 'variant_unavailable');
    }

    public function test_invalid_quantity_is_rejected(): void
    {
        $this->postJson('/api/v1/cart/items', ['variant_public_id' => $this->variant->public_id, 'quantity' => 0])
            ->assertUnprocessable()->assertJsonPath('meta.errors.0.field', 'quantity');
    }

    public function test_authenticated_customer_uses_customer_cart(): void
    {
        $customer = Customer::factory()->create();
        $this->actingAs($customer, 'customer')->postJson('/api/v1/cart/items', ['variant_public_id' => $this->variant->public_id, 'quantity' => 1])
            ->assertCreated()->assertJsonPath('data.item_count', 1);
        $this->assertDatabaseHas('carts', ['customer_id' => $customer->id, 'guest_token_hash' => null]);
    }

    private function addGuestItem(int $quantity): array
    {
        $token = Str::random(64);
        Cart::query()->create(['guest_token_hash' => hash('sha256', $token), 'status' => 'active', 'currency' => 'IDR', 'expires_at' => now()->addDays(30)]);
        $response = $this->withUnencryptedCookie('noure_cart', $token)->withCredentials()->postJson('/api/v1/cart/items', ['variant_public_id' => $this->variant->public_id, 'quantity' => $quantity])->assertCreated();

        return ['noure_cart', $token, $response->json('data.items.0.id')];
    }
}
