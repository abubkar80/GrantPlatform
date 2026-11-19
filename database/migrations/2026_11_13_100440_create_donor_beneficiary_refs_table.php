<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Opaque donor-facing reference. Query authorization and suppression of identifying combinations still required.
        Schema::create('donor_beneficiary_refs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fund_id');
            $table->unsignedBigInteger('student_id');
            $table->char('code', 26)->unique();
            $table->dateTime('created_at', 6);
            $table->unique(['fund_id', 'student_id'], 'donor_beneficiary_refs_fund_id_student_id_unique');
            $table->foreign('fund_id', 'donor_beneficiary_refs_fund_id_foreign')->references('id')->on('funds')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('student_id', 'donor_beneficiary_refs_student_id_foreign')->references('id')->on('student_profiles')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donor_beneficiary_refs');
    }
};
