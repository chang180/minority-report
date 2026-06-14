<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_provider_settings', function (Blueprint $table) {
            $table->json('provider_options')->nullable()->after('model');
        });

        Schema::table('user_custom_providers', function (Blueprint $table) {
            $table->json('provider_options')->nullable()->after('model');
        });
    }

    public function down(): void
    {
        Schema::table('user_custom_providers', function (Blueprint $table) {
            $table->dropColumn('provider_options');
        });

        Schema::table('user_provider_settings', function (Blueprint $table) {
            $table->dropColumn('provider_options');
        });
    }
};
