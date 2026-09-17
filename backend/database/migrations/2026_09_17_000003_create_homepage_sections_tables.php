<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_sections', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('name', 160);
            $table->string('type', 50);
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('configuration')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'sort_order']);
        });
        Schema::create('homepage_section_categories', function (Blueprint $table): void {
            $table->foreignId('homepage_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['homepage_section_id', 'category_id']);
        });
        Schema::create('homepage_section_products', function (Blueprint $table): void {
            $table->foreignId('homepage_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->primary(['homepage_section_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_section_products');
        Schema::dropIfExists('homepage_section_categories');
        Schema::dropIfExists('homepage_sections');
    }
};
