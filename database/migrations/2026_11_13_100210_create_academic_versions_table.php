<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Submitted/verified/rejected. Final decisions immutable; corrections create new version. 0 <= GPA,CGPA <= positive scale; 0 <= passed <= registered credits.
        Schema::create('academic_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academic_record_id');
            $table->unsignedInteger('version_no');
            $table->decimal('gpa', 5, 2);
            $table->decimal('cgpa', 5, 2);
            $table->decimal('grading_scale', 5, 2);
            $table->decimal('registered_credits', 7, 2);
            $table->decimal('passed_credits', 7, 2);
            $table->string('status', 24);
            $table->unsignedBigInteger('submitted_by');
            $table->dateTime('submitted_at', 6);
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->dateTime('verified_at', 6)->nullable();
            $table->text('review_reason')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['academic_record_id', 'version_no'], 'academic_versions_academic_record_id_version_no_unique');
            $table->foreign('academic_record_id', 'academic_versions_academic_record_id_foreign')->references('id')->on('academic_records')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('submitted_by', 'academic_versions_submitted_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('verified_by', 'academic_versions_verified_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_versions');
    }
};
