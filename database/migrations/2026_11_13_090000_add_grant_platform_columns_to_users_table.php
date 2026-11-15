<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Starter-kit users table is framework-owned; we only add the platform columns (no role column: Spatie owns roles).
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_status', 24)->default('active');
            $table->dateTime('disabled_at', 6)->nullable();
            $table->unsignedInteger('row_version')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['account_status', 'disabled_at', 'row_version']);
        });
    }
};
