<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Validate all linked student/application identities. NULL academic version only for approved no-CGPA pathway.
        Schema::create('match_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_version_id');
            $table->unsignedBigInteger('assessment_id');
            $table->unsignedBigInteger('rule_id');
            $table->unsignedBigInteger('academic_version_id')->nullable();
            $table->boolean('eligible');
            $table->json('checks');
            $table->unsignedBigInteger('evaluated_by');
            $table->dateTime('evaluated_at', 6);
            $table->dateTime('created_at', 6);
            $table->foreign('application_version_id', 'match_evaluations_application_version_id_foreign')->references('id')->on('application_versions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('assessment_id', 'match_evaluations_assessment_id_foreign')->references('id')->on('need_assessments')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('rule_id', 'match_evaluations_rule_id_foreign')->references('id')->on('fund_rule_versions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('academic_version_id', 'match_evaluations_academic_version_id_foreign')->references('id')->on('academic_versions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('evaluated_by', 'match_evaluations_evaluated_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_evaluations');
    }
};
