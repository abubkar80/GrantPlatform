<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only evidence link; purpose is specific to the linked record.
        Schema::create('disbursement_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('disbursement_id');
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('linked_by');
            $table->string('purpose', 40);
            $table->dateTime('created_at', 6);
            $table->primary(['disbursement_id', 'document_id', 'purpose']);
            $table->foreign('disbursement_id', 'disbursement_documents_disbursement_id_foreign')->references('id')->on('disbursements')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('document_id', 'disbursement_documents_document_id_foreign')->references('id')->on('documents')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('linked_by', 'disbursement_documents_linked_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disbursement_documents');
    }
};
