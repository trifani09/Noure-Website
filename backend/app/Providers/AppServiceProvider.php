<?php

namespace App\Providers;

use App\Payments\MidtransPaymentGateway;
use App\Payments\PaymentGatewayInterface;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Events\PaymentStatusChanged;
use App\Listeners\SendTransactionalOrderEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, MidtransPaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(OrderCreated::class, SendTransactionalOrderEmail::class);
        Event::listen(PaymentStatusChanged::class, SendTransactionalOrderEmail::class);
        Event::listen(OrderStatusChanged::class, SendTransactionalOrderEmail::class);
    }
}
