<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One active award per student. Proposed: suspended retains slot and reservation. Closure removes slot transactionally.
        Schema::create('active_award_slots', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id')->primary();
            $table->unsignedBigInteger('award_id')->unique();
            $table->dateTime('created_at', 6);
            $table->foreign('student_id', 'active_award_slots_student_id_foreign')->references('id')->on('student_profiles')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign(['award_id', 'student_id'], 'active_award_slots_award_id_student_id_foreign')->references(['id', 'student_id'])->on('awards')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_award_slots');
    }
};
