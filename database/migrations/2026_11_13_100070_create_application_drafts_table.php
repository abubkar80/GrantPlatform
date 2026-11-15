<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_drafts', function (Blueprint $table) {
            $table->unsignedBigInteger('application_id')->primary();
            $table->json('payload');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->foreign('application_id', 'application_drafts_application_id_foreign')->references('id')->on('applications')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_drafts');
    }
};
