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
        Schema::create('verification_requests', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->string('classified_type', 1)->nullable()->index();
            $table->string('classifier_confidence', 10)->nullable()->index();
            $table->string('answer_shape', 20)->nullable()->index();
            $table->boolean('requires_grounding')->default(false);
            $table->boolean('grounding_available')->default(false);
            $table->json('consensus_summary')->nullable();
            $table->string('final_trust', 20)->nullable()->index();
            $table->text('final_verdict')->nullable();
            $table->json('errors')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_requests');
    }
};
