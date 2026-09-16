<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('provider', 100);
            $table->string('provider_payment_id', 255)->nullable();
            $table->string('method_type', 100);
            $table->string('status', 30);
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3);
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_code', 100)->nullable();
            $table->string('failure_message', 500)->nullable();
            $table->string('idempotency_key', 255)->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_payment_id']);
            $table->index(['order_id', 'status']);
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->string('type', 30);
            $table->string('status', 30);
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3);
            $table->string('provider_transaction_id', 255);
            $table->string('idempotency_key', 255)->unique();
            $table->json('response_metadata')->nullable();
            $table->timestamp('processed_at');
            $table->timestamps();

            $table->unique(['payment_id', 'provider_transaction_id']);
            $table->index(['payment_id', 'processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
    }
};
