<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pass/breach. Use Admin-verified values only; compare without rounding.
        Schema::create('benchmark_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('award_id');
            $table->unsignedBigInteger('academic_version_id');
            $table->unsignedBigInteger('rule_id');
            $table->string('engine_version', 40);
            $table->dateTime('evaluated_at', 6);
            $table->string('outcome', 24);
            $table->json('input_snapshot');
            $table->json('checks');
            $table->dateTime('created_at', 6);
            $table->unique(['award_id', 'academic_version_id', 'rule_id', 'engine_version'], 'benchmark_evaluations_award_id_academic_version_id__6d44fc7e');
            $table->foreign('award_id', 'benchmark_evaluations_award_id_foreign')->references('id')->on('awards')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('academic_version_id', 'benchmark_evaluations_academic_version_id_foreign')->references('id')->on('academic_versions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('rule_id', 'benchmark_evaluations_rule_id_foreign')->references('id')->on('fund_rule_versions')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benchmark_evaluations');
    }
};
