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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('name', 160);
            $table->string('placement', 100);
            $table->string('headline', 255)->nullable();
            $table->text('subheading')->nullable();
            $table->string('cta_label', 120)->nullable();
            $table->string('cta_url', 2048)->nullable();
            $table->string('desktop_image_path', 2048);
            $table->string('mobile_image_path', 2048)->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['placement', 'is_active', 'starts_at', 'ends_at', 'sort_order', 'deleted_at'],
                'banners_placement_schedule_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
