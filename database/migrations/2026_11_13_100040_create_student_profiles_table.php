<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enrolment: pending, active, deferred, withdrawn, suspended, completed. Validate programme belongs to institution.
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('institution_id')->nullable();
            $table->unsignedBigInteger('programme_id')->nullable();
            $table->string('matric_number', 80)->nullable();
            $table->unsignedInteger('level')->nullable();
            $table->unsignedBigInteger('origin_lga_id')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('enrolment_status', 24)->default('pending');
            $table->unsignedInteger('row_version')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['institution_id', 'matric_number'], 'student_profiles_institution_id_matric_number_unique');
            $table->foreign('user_id', 'student_profiles_user_id_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('institution_id', 'student_profiles_institution_id_foreign')->references('id')->on('institutions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('programme_id', 'student_profiles_programme_id_foreign')->references('id')->on('programmes')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('origin_lga_id', 'student_profiles_origin_lga_id_foreign')->references('id')->on('local_governments')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
