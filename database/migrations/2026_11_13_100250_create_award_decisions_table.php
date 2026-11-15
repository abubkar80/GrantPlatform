<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('award_decisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('award_id');
            $table->unsignedBigInteger('actor_id');
            $table->string('from_status', 24)->nullable();
            $table->string('to_status', 24);
            $table->string('action', 24);
            $table->text('reason');
            $table->dateTime('created_at', 6);
            $table->foreign('award_id', 'award_decisions_award_id_foreign')->references('id')->on('awards')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('actor_id', 'award_decisions_actor_id_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('award_decisions');
    }
};
