<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAuthenticationApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withServerVariables(['HTTP_ORIGIN' => 'http://localhost:3000']);
    }

    public function test_customer_can_register_and_email_is_normalized(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Alya', 'last_name' => 'Putri',
            'email' => ' ALYA@Example.COM ', 'phone' => '+62 812-3456-7890',
            'password' => 'StrongPass1', 'password_confirmation' => 'StrongPass1',
        ]);

        $response->assertCreated()->assertJsonPath('data.email', 'alya@example.com')
            ->assertJsonMissingPath('data.id')->assertJsonMissingPath('data.password');
        $customer = Customer::query()->sole();
        $this->assertTrue(Hash::check('StrongPass1', $customer->password));
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_duplicate_email_is_rejected(): void
    {
        Customer::factory()->create(['email' => 'alya@example.com']);
        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Alya', 'last_name' => 'Putri', 'email' => 'ALYA@example.com',
            'phone' => '+628123456789', 'password' => 'StrongPass1', 'password_confirmation' => 'StrongPass1',
        ])->assertUnprocessable()->assertJsonPath('meta.errors.0.field', 'email');
    }

    public function test_weak_password_is_rejected(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Alya', 'last_name' => 'Putri', 'email' => 'alya@example.com',
            'phone' => '+628123456789', 'password' => 'password', 'password_confirmation' => 'password',
        ])->assertUnprocessable()->assertJsonPath('meta.errors.0.field', 'password');
    }

    public function test_customer_can_login(): void
    {
        $customer = Customer::factory()->create(['email' => 'alya@example.com', 'password' => 'StrongPass1']);
        $this->postJson('/api/v1/auth/login', ['email' => ' ALYA@example.com ', 'password' => 'StrongPass1', 'remember' => true])
            ->assertOk()->assertJsonPath('data.public_id', $customer->public_id);
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_login_claims_the_active_guest_cart(): void
    {
        $token = 'guest-cart-token';
        $cart = Cart::factory()->create(['guest_token_hash' => hash('sha256', $token)]);
        $variant = ProductVariant::factory()->for(Product::factory())->create();
        CartItem::factory()->for($cart)->for($variant, 'variant')->create(['quantity' => 2]);
        $customer = Customer::factory()->create(['email' => 'alya@example.com', 'password' => 'StrongPass1']);

        $this->withUnencryptedCookie('noure_cart', $token)->withCredentials()
            ->postJson('/api/v1/auth/login', ['email' => 'alya@example.com', 'password' => 'StrongPass1'])
            ->assertOk();

        $this->assertDatabaseHas('carts', ['id' => $cart->id, 'customer_id' => $customer->id, 'guest_token_hash' => null]);
        $this->assertDatabaseHas('cart_items', ['cart_id' => $cart->id, 'variant_id' => $variant->id, 'quantity' => 2]);
    }

    public function test_wrong_password_is_rejected(): void
    {
        Customer::factory()->create(['email' => 'alya@example.com', 'password' => 'StrongPass1']);
        $this->postJson('/api/v1/auth/login', ['email' => 'alya@example.com', 'password' => 'WrongPass1'])
            ->assertUnauthorized()->assertJsonPath('meta.errors.0.code', 'unauthenticated');
    }

    public function test_authenticated_customer_can_fetch_me_and_profile(): void
    {
        $customer = Customer::factory()->create();
        $this->actingAs($customer, 'customer')->getJson('/api/v1/auth/me')
            ->assertOk()->assertJsonPath('data.email', $customer->email);
        $this->actingAs($customer, 'customer')->getJson('/api/v1/customer/profile')
            ->assertOk()->assertJsonPath('data.public_id', $customer->public_id);
    }

    public function test_unauthenticated_me_is_rejected(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized()
            ->assertJsonPath('meta.errors.0.code', 'unauthenticated');
    }

    public function test_customer_can_update_profile_but_not_email(): void
    {
        $customer = Customer::factory()->create(['email' => 'fixed@example.com']);
        $this->actingAs($customer, 'customer')->putJson('/api/v1/customer/profile', [
            'first_name' => 'Nadia', 'last_name' => 'Sari', 'phone' => '+6281211112222',
            'email' => 'changed@example.com',
        ])->assertOk()->assertJsonPath('data.first_name', 'Nadia')->assertJsonPath('data.email', 'fixed@example.com');
        $this->assertSame('fixed@example.com', $customer->refresh()->email);
    }

    public function test_logout_invalidates_customer_session(): void
    {
        $customer = Customer::factory()->create();
        $this->actingAs($customer, 'customer')->postJson('/api/v1/auth/logout')
            ->assertOk()->assertJsonPath('message', 'Logged out successfully.');
        $this->assertGuest('customer');
    }

    public function test_admin_session_cannot_access_customer_profile(): void
    {
        $this->actingAs(User::factory()->create(), 'web')
            ->getJson('/api/v1/customer/profile')->assertUnauthorized();
    }
}
