<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrolment_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('actor_id');
            $table->unsignedBigInteger('evidence_id')->nullable();
            $table->string('from_status', 24);
            $table->string('to_status', 24);
            $table->dateTime('effective_at', 6);
            $table->text('reason');
            $table->dateTime('created_at', 6);
            $table->foreign('student_id', 'enrolment_events_student_id_foreign')->references('id')->on('student_profiles')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('actor_id', 'enrolment_events_actor_id_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('evidence_id', 'enrolment_events_evidence_id_foreign')->references('id')->on('documents')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrolment_events');
    }
};
