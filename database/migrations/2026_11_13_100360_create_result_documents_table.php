<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only evidence link; purpose is specific to the linked record.
        Schema::create('result_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('academic_version_id');
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('linked_by');
            $table->dateTime('created_at', 6);
            $table->primary(['academic_version_id', 'document_id']);
            $table->foreign('academic_version_id', 'result_documents_academic_version_id_foreign')->references('id')->on('academic_versions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('document_id', 'result_documents_document_id_foreign')->references('id')->on('documents')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('linked_by', 'result_documents_linked_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_documents');
    }
};
