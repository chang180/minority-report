<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('provider_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_request_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->index();
            $table->string('model')->nullable();
            $table->text('provider_prompt')->nullable();
            $table->string('provider_status')->default('provider_unavailable')->index();
            $table->text('extraction_prompt')->nullable();
            $table->string('extractor_model')->nullable();
            $table->string('extraction_status')->default('not_started')->index();
            $table->longText('raw_answer')->nullable();
            $table->json('normalized')->nullable();
            $table->json('usage')->nullable();
            $table->json('error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['verification_request_id', 'provider']);
            $table->index(['verification_request_id', 'provider_status']);
            $table->index(['verification_request_id', 'extraction_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_responses');
    }
};
