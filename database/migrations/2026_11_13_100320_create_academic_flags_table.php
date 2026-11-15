<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Benchmark breach/enrolment change/overdue result. Open blocking flags prevent release. Overdue alone does not block.
        Schema::create('academic_flags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('award_id');
            $table->unsignedBigInteger('evaluation_id')->nullable();
            $table->unsignedBigInteger('period_id')->nullable();
            $table->string('kind', 32);
            $table->boolean('blocks_release');
            $table->string('status', 24);
            $table->string('source_key', 160)->unique();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->dateTime('resolved_at', 6)->nullable();
            $table->string('resolution', 24)->nullable();
            $table->text('reason')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->foreign('award_id', 'academic_flags_award_id_foreign')->references('id')->on('awards')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('evaluation_id', 'academic_flags_evaluation_id_foreign')->references('id')->on('benchmark_evaluations')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('period_id', 'academic_flags_period_id_foreign')->references('id')->on('academic_periods')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('resolved_by', 'academic_flags_resolved_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_flags');
    }
};
