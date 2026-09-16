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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('code', 100)->unique();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->string('type', 30);
            $table->unsignedBigInteger('value');
            $table->char('currency', 3)->nullable();
            $table->unsignedBigInteger('minimum_order_amount')->nullable();
            $table->unsignedBigInteger('maximum_discount_amount')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_customer')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'starts_at', 'ends_at', 'deleted_at']);
        });

        Schema::create('discount_products', function (Blueprint $table) {
            $table->foreignId('discount_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->primary(['discount_id', 'product_id']);
        });

        Schema::create('discount_categories', function (Blueprint $table) {
            $table->foreignId('discount_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();

            $table->primary(['discount_id', 'category_id']);
        });

        Schema::create('discount_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code_snapshot', 100);
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['discount_id', 'order_id']);
            $table->index(['customer_id', 'discount_id']);
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_redemptions');
        Schema::dropIfExists('discount_categories');
        Schema::dropIfExists('discount_products');
        Schema::dropIfExists('discounts');
    }
};
