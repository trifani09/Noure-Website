<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.midtrans.server_key' => 'test-server-key', 'services.midtrans.production' => false]);
        $this->customer = Customer::factory()->create();
        $this->order = Order::factory()->for($this->customer)->create([
            'order_number' => 'NOU-PAY-TEST', 'payment_status' => 'unpaid', 'subtotal_amount' => 300000,
            'shipping_amount' => 0, 'grand_total_amount' => 300000, 'currency' => 'IDR',
        ]);
    }

    public function test_customer_can_create_payment_with_backend_amount(): void
    {
        Http::fake(['app.sandbox.midtrans.com/*' => Http::response(['token' => 'snap-token', 'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/token'], 201)]);
        $this->actingAs($this->customer, 'customer')->withHeader('Idempotency-Key', 'payment-test-key')
            ->postJson('/api/v1/orders/'.$this->order->public_id.'/payment', ['amount' => 1])
            ->assertCreated()->assertJsonPath('data.amount', 300000)->assertJsonPath('data.redirect_url', 'https://app.sandbox.midtrans.com/snap/v4/redirection/token');
        $this->assertDatabaseHas('payments', ['order_id' => $this->order->id, 'amount' => 300000, 'currency' => 'IDR', 'provider' => 'midtrans']);
        Http::assertSent(fn ($request) => $request['transaction_details']['gross_amount'] === 300000);
    }

    public function test_invalid_order_is_rejected(): void
    {
        Http::fake();
        $this->actingAs($this->customer, 'customer')->postJson('/api/v1/orders/01AAAAAAAAAAAAAAAAAAAAAAAA/payment')
            ->assertNotFound()->assertJsonPath('meta.errors.0.code', 'order_not_found');
        Http::assertNothingSent();
    }

    public function test_already_paid_order_is_rejected(): void
    {
        $this->order->update(['payment_status' => 'paid']);
        $this->actingAs($this->customer, 'customer')->postJson('/api/v1/orders/'.$this->order->public_id.'/payment')
            ->assertConflict()->assertJsonPath('meta.errors.0.code', 'order_already_paid');
    }

    public function test_invalid_webhook_signature_is_rejected(): void
    {
        $this->postJson('/api/v1/payments/webhook', $this->webhook(['signature_key' => 'invalid']))
            ->assertUnauthorized()->assertJsonPath('meta.errors.0.code', 'invalid_webhook_signature');
    }

    public function test_valid_paid_webhook_updates_payment_and_order(): void
    {
        $payment = $this->payment();
        $payload = $this->signedWebhook();
        $this->postJson('/api/v1/payments/webhook', $payload)->assertOk()->assertJsonPath('data.status', 'paid');
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertDatabaseHas('orders', ['id' => $this->order->id, 'payment_status' => 'paid', 'status' => 'pending']);
        $this->assertDatabaseHas('payment_transactions', ['payment_id' => $payment->id, 'status' => 'paid']);
    }

    public function test_duplicate_webhook_is_idempotent(): void
    {
        $this->payment();
        $payload = $this->signedWebhook();
        $this->postJson('/api/v1/payments/webhook', $payload)->assertOk();
        $this->postJson('/api/v1/payments/webhook', $payload)->assertOk();
        $this->assertDatabaseCount('payment_transactions', 1);
    }

    public function test_failed_payment_is_preserved_without_deleting_order(): void
    {
        $payment = $this->payment();
        $payload = $this->signedWebhook(['transaction_status' => 'deny', 'status_code' => '202']);
        $this->postJson('/api/v1/payments/webhook', $payload)->assertOk()->assertJsonPath('data.status', 'failed');
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed']);
        $this->assertDatabaseHas('orders', ['id' => $this->order->id, 'payment_status' => 'failed']);
    }

    private function payment(): Payment
    {
        return Payment::factory()->for($this->order)->create([
            'provider' => 'midtrans', 'provider_payment_id' => $this->order->order_number,
            'status' => 'pending', 'amount' => 300000, 'currency' => 'IDR',
        ]);
    }

    /** @param array<string, string> $overrides
     * @return array<string, string>
     */
    private function signedWebhook(array $overrides = []): array
    {
        $payload = $this->webhook($overrides);
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].'test-server-key');

        return $payload;
    }

    /** @param array<string, string> $overrides
     * @return array<string, string>
     */
    private function webhook(array $overrides = []): array
    {
        return [...[
            'order_id' => $this->order->order_number, 'status_code' => '200', 'gross_amount' => '300000.00',
            'signature_key' => '', 'transaction_status' => 'settlement', 'transaction_id' => 'midtrans-transaction-1',
            'payment_type' => 'bank_transfer', 'currency' => 'IDR', 'status_message' => 'Payment notification',
        ], ...$overrides];
    }
}
