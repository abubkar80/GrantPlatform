<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pending/complete. Finalized rows immutable. Complete requires verified inputs and component total; combined circumstances capped at 10.
        Schema::create('need_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_version_id');
            $table->unsignedBigInteger('policy_id');
            $table->unsignedBigInteger('assessor_id');
            $table->string('status', 24);
            $table->dateTime('assessed_at', 6)->nullable();
            $table->json('verified_inputs');
            $table->json('verification_map');
            $table->string('risk_level', 16)->nullable();
            $table->text('risk_reason')->nullable();
            $table->unsignedInteger('income_points')->nullable();
            $table->unsignedInteger('costs_points')->nullable();
            $table->unsignedInteger('support_points')->nullable();
            $table->unsignedInteger('dependant_points')->nullable();
            $table->unsignedInteger('circumstance_points')->nullable();
            $table->unsignedInteger('risk_points')->nullable();
            $table->unsignedInteger('total_score')->nullable();
            $table->string('category', 16)->nullable();
            $table->unsignedBigInteger('verified_unmet_cost')->nullable();
            $table->text('funding_gap_note')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->foreign('application_version_id', 'need_assessments_application_version_id_foreign')->references('id')->on('application_versions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('policy_id', 'need_assessments_policy_id_foreign')->references('id')->on('scoring_policies')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('assessor_id', 'need_assessments_assessor_id_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('need_assessments');
    }
};
