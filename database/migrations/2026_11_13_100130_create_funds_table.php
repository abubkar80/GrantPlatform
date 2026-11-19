<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Draft/open/paused/closed. NGN only proposed. Lock anchor for financial commands; intended amount is not cash.
        Schema::create('funds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('donor_id');
            $table->string('name', 180);
            $table->char('currency', 3)->default('NGN');
            $table->string('status', 24);
            $table->unsignedBigInteger('intended_amount')->nullable();
            $table->dateTime('criteria_locked_at', 6)->nullable();
            $table->unsignedInteger('row_version')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['donor_id', 'status'], 'funds_donor_id_status_index');
            $table->foreign('donor_id', 'funds_donor_id_foreign')->references('id')->on('donor_profiles')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funds');
    }
};
