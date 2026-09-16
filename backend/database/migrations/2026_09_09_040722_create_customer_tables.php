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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('email')->nullable()->unique();
            $table->string('phone', 50)->nullable()->index();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('status', 30)->default('active');
            $table->timestamp('marketing_consent_at')->nullable();
            $table->timestamp('last_order_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'deleted_at']);
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label', 100)->nullable();
            $table->string('recipient_name', 200);
            $table->string('phone', 50);
            $table->string('line1', 255);
            $table->string('line2', 255)->nullable();
            $table->string('city', 120);
            $table->string('province', 120)->nullable();
            $table->string('postal_code', 30);
            $table->char('country_code', 2);
            $table->boolean('is_default_shipping')->default(false);
            $table->boolean('is_default_billing')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'deleted_at']);
            $table->index(['customer_id', 'is_default_shipping']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('customers');
    }
};
