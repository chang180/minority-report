<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_grounding_settings', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 32)->default('disabled');
            $table->boolean('enabled')->default(false);
            $table->string('local_api_url', 512)->nullable();
            $table->string('local_model', 128)->nullable();
            $table->text('local_api_key')->nullable();
            $table->string('search_provider', 32)->nullable();
            $table->text('search_api_key')->nullable();
            $table->string('search_api_url', 512)->nullable();
            $table->unsignedSmallInteger('max_tool_rounds')->default(4);
            $table->unsignedSmallInteger('timeout_seconds')->default(120);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_grounding_settings');
    }
};
