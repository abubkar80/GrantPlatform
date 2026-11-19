<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only evidence link; purpose is specific to the linked record.
        Schema::create('contribution_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('contribution_id');
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('linked_by');
            $table->string('purpose', 40);
            $table->dateTime('created_at', 6);
            $table->primary(['contribution_id', 'document_id', 'purpose']);
            $table->foreign('contribution_id', 'contribution_documents_contribution_id_foreign')->references('id')->on('contributions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('document_id', 'contribution_documents_document_id_foreign')->references('id')->on('documents')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('linked_by', 'contribution_documents_linked_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contribution_documents');
    }
};
