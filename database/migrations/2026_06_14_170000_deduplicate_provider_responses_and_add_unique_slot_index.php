<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $keepIds = DB::table('provider_responses')
            ->selectRaw('MAX(id) as id')
            ->groupBy('verification_request_id', 'provider')
            ->pluck('id');

        if ($keepIds->isNotEmpty()) {
            DB::table('provider_responses')
                ->whereNotIn('id', $keepIds->all())
                ->delete();
        }

        Schema::table('provider_responses', function (Blueprint $table) {
            $table->unique(['verification_request_id', 'provider'], 'provider_responses_verification_provider_unique');
        });
    }

    public function down(): void
    {
        Schema::table('provider_responses', function (Blueprint $table) {
            $table->dropUnique('provider_responses_verification_provider_unique');
        });
    }
};
