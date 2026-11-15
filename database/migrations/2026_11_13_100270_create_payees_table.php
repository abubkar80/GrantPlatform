<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kind institution/supplier only; institution requires institution_id. Encrypt payment_details. No student payees.
        Schema::create('payees', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 16);
            $table->string('name', 180);
            $table->unsignedBigInteger('institution_id')->nullable();
            $table->text('payment_details');
            $table->string('verification_status', 24);
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->dateTime('verified_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->foreign('institution_id', 'payees_institution_id_foreign')->references('id')->on('institutions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('verified_by', 'payees_verified_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payees');
    }
};
