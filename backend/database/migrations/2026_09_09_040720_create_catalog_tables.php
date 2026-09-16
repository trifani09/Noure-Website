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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->string('name', 160);
            $table->string('slug', 180);
            $table->text('description')->nullable();
            $table->string('image_path', 2048)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->string('active_slug', 180)
                ->nullable()
                ->virtualAs('CASE WHEN deleted_at IS NULL THEN slug ELSE NULL END');

            $table->unique('active_slug');
            $table->index(['parent_id', 'sort_order']);
            $table->index(['is_active', 'deleted_at']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('name', 200)->index();
            $table->string('slug', 220);
            $table->string('short_description', 500)->nullable();
            $table->longText('description')->nullable();
            $table->string('status', 30)->default('draft');
            $table->string('brand', 160)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->string('active_slug', 220)
                ->nullable()
                ->virtualAs('CASE WHEN deleted_at IS NULL THEN slug ELSE NULL END');

            $table->unique('active_slug');
            $table->index(['status', 'published_at', 'deleted_at']);
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->primary(['product_id', 'category_id']);
            $table->index(['category_id', 'sort_order']);
        });

        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'code']);
            $table->index(['product_id', 'sort_order']);
        });

        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            $table->string('label', 120);
            $table->string('code', 120);
            $table->string('swatch_value', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_option_id', 'code']);
            $table->index(['product_option_id', 'sort_order']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 100)->unique();
            $table->string('title')->nullable();
            $table->string('combination_key', 500);
            $table->unsignedBigInteger('price_amount');
            $table->unsignedBigInteger('compare_at_amount')->nullable();
            $table->char('currency', 3);
            $table->string('barcode', 100)->nullable()->unique();
            $table->unsignedInteger('weight_grams')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'combination_key']);
            $table->index(['product_id', 'is_active', 'deleted_at']);
        });

        Schema::create('variant_option_values', function (Blueprint $table) {
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('product_option_value_id')->constrained()->cascadeOnDelete();

            $table->primary(['variant_id', 'product_option_value_id']);
            $table->index('product_option_value_id');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->string('path', 2048);
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
            $table->index(['variant_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('variant_option_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_options');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
