<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_commands', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id');
            $table->string('operation', 48);
            $table->string('idempotency_key', 100);
            $table->char('request_hash', 64);
            $table->json('result_reference');
            $table->dateTime('completed_at', 6);
            $table->dateTime('created_at', 6);
            $table->unique(['actor_id', 'operation', 'idempotency_key'], 'financial_commands_actor_id_operation_idempotency_key_unique');
            $table->foreign('actor_id', 'financial_commands_actor_id_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_commands');
    }
};
