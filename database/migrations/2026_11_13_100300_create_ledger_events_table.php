<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only. Receipt: +cash/0 reserved; commitment: 0/+reserved; release: -cash/-reserved; commitment_release: 0/-reserved. Positive amount; enforce source combinations and deltas. No refund events until approved.
        Schema::create('ledger_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fund_id');
            $table->unsignedBigInteger('award_id')->nullable();
            $table->unsignedBigInteger('contribution_id')->nullable()->unique();
            $table->unsignedBigInteger('disbursement_id')->nullable()->unique();
            $table->string('kind', 32);
            $table->unsignedBigInteger('amount');
            $table->bigInteger('cash_delta');
            $table->bigInteger('reserved_delta');
            $table->string('source_event_key', 160)->unique();
            $table->unsignedBigInteger('command_id');
            $table->unsignedBigInteger('actor_id');
            $table->dateTime('occurred_at', 6);
            $table->dateTime('created_at', 6);
            $table->index(['fund_id', 'occurred_at', 'id'], 'ledger_events_fund_id_occurred_at_id_index');
            $table->index(['award_id', 'id'], 'ledger_events_award_id_id_index');
            $table->foreign('fund_id', 'ledger_events_fund_id_foreign')->references('id')->on('funds')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('award_id', 'ledger_events_award_id_foreign')->references('id')->on('awards')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('contribution_id', 'ledger_events_contribution_id_foreign')->references('id')->on('contributions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('disbursement_id', 'ledger_events_disbursement_id_foreign')->references('id')->on('disbursements')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('command_id', 'ledger_events_command_id_foreign')->references('id')->on('financial_commands')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('actor_id', 'ledger_events_actor_id_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        // CHECK constraints are MySQL-only; SQLite (used by the default test config) cannot ALTER TABLE ADD CONSTRAINT.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE ledger_events ADD CONSTRAINT ledger_events_amount_positive CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_events');
    }
};
