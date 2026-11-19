<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pending/received/rejected. Amount > 0. Received only after Admin verifies bank receipt and evidence.
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fund_id');
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3);
            $table->string('status', 24)->default('pending');
            $table->string('transfer_reference', 160)->nullable();
            $table->unsignedBigInteger('reported_by');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->dateTime('received_at', 6)->nullable();
            $table->dateTime('verified_at', 6)->nullable();
            $table->text('reason')->nullable();
            $table->unsignedInteger('row_version')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['fund_id', 'status'], 'contributions_fund_id_status_index');
            $table->index('transfer_reference', 'contributions_transfer_reference_index');
            $table->foreign('fund_id', 'contributions_fund_id_foreign')->references('id')->on('funds')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('reported_by', 'contributions_reported_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('verified_by', 'contributions_verified_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        // CHECK constraints are MySQL-only; SQLite (used by the default test config) cannot ALTER TABLE ADD CONSTRAINT.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE contributions ADD CONSTRAINT contributions_amount_positive CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
