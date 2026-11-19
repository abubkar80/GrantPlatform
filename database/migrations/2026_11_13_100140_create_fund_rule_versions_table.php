<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable after publication. Percentages 0–100. Pin version on match; criteria lock on first release.
        Schema::create('fund_rule_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fund_id');
            $table->unsignedInteger('version_no');
            $table->unsignedBigInteger('created_by');
            $table->unsignedInteger('min_need_score')->nullable();
            $table->string('need_category', 16)->nullable();
            $table->decimal('min_cgpa_percent', 7, 4)->nullable();
            $table->boolean('allows_no_cgpa')->default(false);
            $table->decimal('progression_percent', 7, 4);
            $table->decimal('min_credits', 7, 2)->nullable();
            $table->boolean('automatic_suspension')->default(false);
            $table->dateTime('effective_at', 6);
            $table->string('evidence_policy_version', 40);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['fund_id', 'version_no'], 'fund_rule_versions_fund_id_version_no_unique');
            $table->foreign('fund_id', 'fund_rule_versions_fund_id_foreign')->references('id')->on('funds')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('created_by', 'fund_rule_versions_created_by_foreign')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_rule_versions');
    }
};
