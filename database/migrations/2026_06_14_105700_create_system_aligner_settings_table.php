<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_aligner_settings', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 32)->default('string');
            $table->boolean('enabled')->default(true);
            $table->string('local_api_url', 512)->nullable();
            $table->string('local_model', 128)->nullable();
            $table->text('local_api_key')->nullable();
            $table->unsignedSmallInteger('timeout_seconds')->default(15);
            $table->string('min_confidence', 16)->default('high');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_aligner_settings');
    }
};
