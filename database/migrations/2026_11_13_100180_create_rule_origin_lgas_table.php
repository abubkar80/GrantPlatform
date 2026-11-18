<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // No rows: unrestricted dimension. Values within dimension are alternatives; dimensions combined with AND. Category semantics require approval.
        Schema::create('rule_origin_lgas', function (Blueprint $table) {
            $table->unsignedBigInteger('rule_id');
            $table->unsignedBigInteger('lga_id');
            $table->dateTime('created_at', 6);
            $table->primary(['rule_id', 'lga_id']);
            $table->foreign('rule_id', 'rule_origin_lgas_rule_id_foreign')->references('id')->on('fund_rule_versions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('lga_id', 'rule_origin_lgas_lga_id_foreign')->references('id')->on('local_governments')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_origin_lgas');
    }
};
