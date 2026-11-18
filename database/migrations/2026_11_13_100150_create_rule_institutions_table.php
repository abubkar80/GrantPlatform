<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No rows: unrestricted dimension. Values within dimension are alternatives; dimensions combined with AND. Category semantics require approval.
        Schema::create('rule_institutions', function (Blueprint $table) {
            $table->unsignedBigInteger('rule_id');
            $table->unsignedBigInteger('institution_id');
            $table->dateTime('created_at', 6);
            $table->primary(['rule_id', 'institution_id']);
            $table->foreign('rule_id', 'rule_institutions_rule_id_foreign')->references('id')->on('fund_rule_versions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('institution_id', 'rule_institutions_institution_id_foreign')->references('id')->on('institutions')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_institutions');
    }
};
