<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Private storage. Immutable file bytes; mutable scan metadata. Scan pending/clean/rejected/error. A clean scan is not verification of authenticity.
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('uploaded_by');
            $table->string('storage_disk', 40);
            $table->string('object_key', 500);
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->string('scan_status', 24)->default('pending');
            $table->dateTime('scanned_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->unique(['storage_disk', 'object_key'], 'documents_storage_disk_object_key_unique');
            $table->foreign('owner_id', 'documents_owner_id_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('uploaded_by', 'documents_uploaded_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
