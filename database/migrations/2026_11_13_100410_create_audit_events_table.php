<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only. subject_id is a logical polymorphic link. Sanitize changes; no passwords, bank details or raw evidence.
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action', 100);
            $table->string('outcome', 24);
            $table->string('subject_type', 80);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('request_id', 100)->nullable();
            $table->text('reason')->nullable();
            $table->json('changes');
            $table->dateTime('occurred_at', 6);
            $table->dateTime('created_at', 6);
            $table->index(['subject_type', 'subject_id', 'occurred_at'], 'audit_events_subject_type_subject_id_occurred_at_index');
            $table->index(['actor_id', 'occurred_at'], 'audit_events_actor_id_occurred_at_index');
            $table->foreign('actor_id', 'audit_events_actor_id_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
