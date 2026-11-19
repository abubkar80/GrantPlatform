<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Granted/withdrawn. Withdrawal starts review; does not erase financial history.
        Schema::create('consent_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('notice_version', 40);
            $table->string('action', 24);
            $table->string('scope', 100);
            $table->dateTime('occurred_at', 6);
            $table->dateTime('created_at', 6);
            $table->foreign('user_id', 'consent_events_user_id_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_events');
    }
};
