<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lock application to enforce one current assignment.
        Schema::create('case_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('assigned_by');
            $table->dateTime('started_at', 6);
            $table->dateTime('ended_at', 6)->nullable();
            $table->text('reason');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['application_id', 'ended_at'], 'case_assignments_application_id_ended_at_index');
            $table->foreign('application_id', 'case_assignments_application_id_foreign')->references('id')->on('applications')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('admin_id', 'case_assignments_admin_id_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('assigned_by', 'case_assignments_assigned_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_assignments');
    }
};
