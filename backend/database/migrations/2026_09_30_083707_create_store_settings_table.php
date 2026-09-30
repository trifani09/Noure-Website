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
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name', 160)->default('Noure');
            $table->string('support_email')->nullable();
            $table->string('support_phone', 50)->nullable();
            $table->string('whatsapp_number', 50)->nullable();
            $table->string('instagram_url', 2048)->nullable();
            $table->char('default_currency', 3)->default('IDR');
            $table->string('timezone', 100)->default('Asia/Jakarta');
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->string('order_prefix', 20)->default('NOU');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
