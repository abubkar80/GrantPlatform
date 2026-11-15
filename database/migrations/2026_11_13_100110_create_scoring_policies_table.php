<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable when published. Weights 30/25/15/10/10/10. Demo cutoffs 35 and 60 require validation.
        Schema::create('scoring_policies', function (Blueprint $table) {
            $table->id();
            $table->string('version', 40)->unique();
            $table->string('status', 24);
            $table->json('rules');
            $table->dateTime('effective_at', 6)->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedInteger('minimum_score');
            $table->unsignedInteger('high_minimum');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->foreign('created_by', 'scoring_policies_created_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scoring_policies');
    }
};
