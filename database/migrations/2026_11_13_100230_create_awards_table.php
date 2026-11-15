<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Active/suspended/terminated/completed. Admin-owned; amount > 0, within verified gap and available funds. Reserve atomically.
        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('fund_id');
            $table->unsignedBigInteger('match_id');
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3);
            $table->string('status', 24);
            $table->json('support_types');
            $table->text('decision_reason');
            $table->unsignedBigInteger('awarded_by');
            $table->dateTime('awarded_at', 6);
            $table->dateTime('closed_at', 6)->nullable();
            $table->unsignedInteger('row_version')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['student_id', 'status'], 'awards_student_id_status_index');
            $table->index(['fund_id', 'status'], 'awards_fund_id_status_index');
            $table->unique(['id', 'student_id'], 'awards_id_student_id_unique');
            $table->foreign('student_id', 'awards_student_id_foreign')->references('id')->on('student_profiles')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('fund_id', 'awards_fund_id_foreign')->references('id')->on('funds')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('match_id', 'awards_match_id_foreign')->references('id')->on('match_evaluations')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('awarded_by', 'awards_awarded_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        // CHECK constraints are MySQL-only; SQLite (used by the default test config) cannot ALTER TABLE ADD CONSTRAINT.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE awards ADD CONSTRAINT awards_amount_positive CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('awards');
    }
};
