<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table): void {
            $table->ulid('public_id')->nullable()->after('id');
        });
        DB::table('product_images')->orderBy('id')->eachById(function (object $image): void {
            DB::table('product_images')->where('id', $image->id)->update(['public_id' => (string) Str::ulid()]);
        });
        Schema::table('product_images', function (Blueprint $table): void {
            $table->unique('public_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table): void {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
