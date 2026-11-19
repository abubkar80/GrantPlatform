<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institution_id');
            $table->string('session_label', 24);
            $table->unsignedInteger('semester');
            $table->dateTime('results_due_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['institution_id', 'session_label', 'semester'], 'academic_periods_institution_id_session_label_semes_5b053cc7');
            $table->foreign('institution_id', 'academic_periods_institution_id_foreign')->references('id')->on('institutions')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_periods');
    }
};
