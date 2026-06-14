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
        Schema::create('system_demo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 32)->default('fake_fixtures');
            $table->boolean('demo_enabled')->default(true);
            $table->string('shared_api_url', 512)->nullable();
            $table->text('shared_api_key')->nullable();
            $table->string('default_fixture_id', 64)->default('M6-F02');
            $table->json('enabled_fixture_ids')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_demo_settings');
    }
};
