<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Scheduled/held/released/confirmed/cancelled. Positive amount. Encrypt payee_snapshot. Release debits once; confirmation requires payee receipt, no second debit. Released payments cannot be cancelled.
        Schema::create('disbursements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('award_id');
            $table->unsignedBigInteger('payee_id');
            $table->unsignedBigInteger('amount');
            $table->string('status', 24)->default('scheduled');
            $table->string('purpose', 255);
            $table->date('scheduled_for')->nullable();
            $table->string('transfer_reference', 160)->nullable();
            $table->text('payee_snapshot')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->unsignedBigInteger('released_by')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->dateTime('released_at', 6)->nullable();
            $table->dateTime('confirmed_at', 6)->nullable();
            $table->text('cancel_reason')->nullable();
            $table->unsignedInteger('row_version')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['award_id', 'status'], 'disbursements_award_id_status_index');
            $table->index(['status', 'scheduled_for'], 'disbursements_status_scheduled_for_index');
            $table->index('transfer_reference', 'disbursements_transfer_reference_index');
            $table->foreign('award_id', 'disbursements_award_id_foreign')->references('id')->on('awards')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('payee_id', 'disbursements_payee_id_foreign')->references('id')->on('payees')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('recorded_by', 'disbursements_recorded_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('released_by', 'disbursements_released_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('confirmed_by', 'disbursements_confirmed_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        // CHECK constraints are MySQL-only; SQLite (used by the default test config) cannot ALTER TABLE ADD CONSTRAINT.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE disbursements ADD CONSTRAINT disbursements_amount_positive CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('disbursements');
    }
};
