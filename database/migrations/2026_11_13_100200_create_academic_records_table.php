<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('period_id');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['student_id', 'period_id'], 'academic_records_student_id_period_id_unique');
            $table->foreign('student_id', 'academic_records_student_id_foreign')->references('id')->on('student_profiles')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('period_id', 'academic_records_period_id_foreign')->references('id')->on('academic_periods')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_records');
    }
};
