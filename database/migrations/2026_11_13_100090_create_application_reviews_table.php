<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Outcome approved/declined/needs_information. Admin only.
        Schema::create('application_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('application_version_id');
            $table->unsignedBigInteger('reviewer_id');
            $table->string('outcome', 24);
            $table->text('reason');
            $table->dateTime('created_at', 6);
            $table->foreign('application_version_id', 'application_reviews_application_version_id_foreign')->references('id')->on('application_versions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('reviewer_id', 'application_reviews_reviewer_id_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_reviews');
    }
};
