<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactional_email_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->string('event_key', 255)->unique();
            $table->string('recipient');
            $table->string('message_type', 100);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactional_email_deliveries');
    }
};