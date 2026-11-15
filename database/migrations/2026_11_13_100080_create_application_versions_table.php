<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable submitted snapshot. Unknown values remain NULL. Orphan is Yes/No. Result availability: available/not_yet_available.
        Schema::create('application_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedInteger('version_no');
            $table->unsignedBigInteger('programme_id');
            $table->string('matric_number', 80)->nullable();
            $table->unsignedInteger('level');
            $table->unsignedBigInteger('origin_lga_id');
            $table->string('enrolment_status', 24);
            $table->unsignedBigInteger('monthly_income')->nullable();
            $table->unsignedBigInteger('tuition')->nullable();
            $table->unsignedBigInteger('prior_arrears')->nullable();
            $table->unsignedBigInteger('education_period_id');
            $table->boolean('external_aid')->nullable();
            $table->unsignedBigInteger('external_aid_amount')->nullable();
            $table->unsignedInteger('dependants')->nullable();
            $table->boolean('is_orphan')->nullable();
            $table->boolean('single_parent')->nullable();
            $table->boolean('disability')->nullable();
            $table->string('result_availability', 24);
            $table->dateTime('declared_at', 6);
            $table->dateTime('created_at', 6);
            $table->unique(['application_id', 'version_no'], 'application_versions_application_id_version_no_unique');
            $table->foreign('application_id', 'application_versions_application_id_foreign')->references('id')->on('applications')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('programme_id', 'application_versions_programme_id_foreign')->references('id')->on('programmes')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('origin_lga_id', 'application_versions_origin_lga_id_foreign')->references('id')->on('local_governments')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('education_period_id', 'application_versions_education_period_id_foreign')->references('id')->on('academic_periods')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_versions');
    }
};
