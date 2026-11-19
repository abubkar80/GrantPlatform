<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only evidence link; purpose is specific to the linked record.
        Schema::create('assessment_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('assessment_id');
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('linked_by');
            $table->string('indicator_code', 40);
            $table->dateTime('created_at', 6);
            $table->primary(['assessment_id', 'document_id', 'indicator_code']);
            $table->foreign('assessment_id', 'assessment_documents_assessment_id_foreign')->references('id')->on('need_assessments')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('document_id', 'assessment_documents_document_id_foreign')->references('id')->on('documents')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('linked_by', 'assessment_documents_linked_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_documents');
    }
};
