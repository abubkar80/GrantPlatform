<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Statuses: draft, submitted, under_review, needs_information, approved, declined. Approval belongs to a reviewed version.
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('status', 24)->default('draft');
            $table->char('public_reference', 26)->unique();
            $table->unsignedInteger('row_version')->default(1);
            $table->dateTime('submitted_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['status', 'submitted_at', 'id'], 'applications_status_submitted_at_id_index');
            $table->index(['student_id', 'created_at'], 'applications_student_id_created_at_index');
            $table->foreign('student_id', 'applications_student_id_foreign')->references('id')->on('student_profiles')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
