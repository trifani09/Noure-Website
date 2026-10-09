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
        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('announcement_text')->default('Dapatkan harga eksklusif hanya di website')->after('store_name');
            $table->string('announcement_url', 2048)->nullable()->after('announcement_text');
            $table->boolean('announcement_is_active')->default(true)->after('announcement_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn(['announcement_text', 'announcement_url', 'announcement_is_active']);
        });
    }
};
