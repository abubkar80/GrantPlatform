<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('local_governments', function (Blueprint $table) {
            $table->id();
            $table->string('state_name', 80);
            $table->string('name', 100);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['state_name', 'name'], 'local_governments_state_name_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('local_governments');
    }
};
