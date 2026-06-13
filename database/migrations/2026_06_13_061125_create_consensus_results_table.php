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
        Schema::create('consensus_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('alignment')->nullable();
            $table->json('conflict_detection')->nullable();
            $table->json('consensus')->nullable();
            $table->string('decision_key')->nullable();
            $table->json('decision_basis')->nullable();
            $table->string('trust_base', 20)->nullable();
            $table->json('applied_caps')->nullable();
            $table->string('trust_level', 20)->nullable()->index();
            $table->json('verdict_report')->nullable();
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
        Schema::dropIfExists('consensus_results');
    }
};
