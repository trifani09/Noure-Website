<?php

namespace Tests\Feature;

use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Events\PaymentStatusChanged;
use App\Listeners\SendTransactionalOrderEmail;
use App\Mail\TransactionalOrderMail;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TransactionalEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_created_dispatches_a_queued_email_for_a_guest_order(): void
    {
        Queue::fake();
        OrderCreated::dispatch(Order::factory()->create(['email' => 'guest@example.com']));

        Queue::assertPushed(CallQueuedListener::class, fn (CallQueuedListener $job): bool => $job->class === SendTransactionalOrderEmail::class);
    }

    public function test_order_created_uses_the_order_recipient_for_an_authenticated_customer(): void
    {
        Mail::fake();
        $customer = Customer::factory()->create(['email' => 'customer@example.com']);
        $order = Order::factory()->for($customer)->create(['email' => 'snapshot@example.com']);

        app(SendTransactionalOrderEmail::class)->handle(new OrderCreated($order));

        Mail::assertSent(function (TransactionalOrderMail $mail): bool {
            return $mail->hasTo('snapshot@example.com') && $mail->messageType === 'order_created';
        });
    }

    public function test_payment_success_and_duplicate_events_create_one_delivery(): void
    {
        Mail::fake();
        $order = Order::factory()->create(['email' => 'paid@example.com']);
        $event = new PaymentStatusChanged($order, 'paid', 'midtrans', 'bank_transfer', 300000, now()->toISOString());
        $listener = app(SendTransactionalOrderEmail::class);

        $listener->handle($event);
        $listener->handle($event);

        Mail::assertSentCount(1);
        Mail::assertSent(TransactionalOrderMail::class, fn (TransactionalOrderMail $mail): bool => $mail->messageType === 'payment_paid');
        $this->assertDatabaseCount('transactional_email_deliveries', 1);
    }

    public function test_payment_failure_and_expiry_are_queued(): void
    {
        Queue::fake();
        $order = Order::factory()->create();

        PaymentStatusChanged::dispatch($order, 'failed');
        PaymentStatusChanged::dispatch($order, 'expired');

        Queue::assertPushed(CallQueuedListener::class, 2);
    }

    public function test_processing_shipped_completed_and_cancelled_statuses_are_queued(): void
    {
        Queue::fake();
        $order = Order::factory()->create();

        foreach (['processing', 'shipped', 'completed', 'cancelled'] as $status) {
            OrderStatusChanged::dispatch($order, $status);
        }

        Queue::assertPushed(CallQueuedListener::class, 4);
    }
}
