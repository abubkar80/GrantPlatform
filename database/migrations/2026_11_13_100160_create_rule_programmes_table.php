<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No rows: unrestricted dimension. Values within dimension are alternatives; dimensions combined with AND. Category semantics require approval.
        Schema::create('rule_programmes', function (Blueprint $table) {
            $table->unsignedBigInteger('rule_id');
            $table->unsignedBigInteger('programme_id');
            $table->dateTime('created_at', 6);
            $table->primary(['rule_id', 'programme_id']);
            $table->foreign('rule_id', 'rule_programmes_rule_id_foreign')->references('id')->on('fund_rule_versions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('programme_id', 'rule_programmes_programme_id_foreign')->references('id')->on('programmes')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_programmes');
    }
};
