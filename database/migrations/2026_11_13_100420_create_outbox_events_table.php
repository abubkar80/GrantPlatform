<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 160)->unique();
            $table->string('event_type', 80);
            $table->json('payload');
            $table->dateTime('available_at', 6);
            $table->dateTime('processed_at', 6)->nullable();
            $table->dateTime('locked_until', 6)->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_events');
    }
};
