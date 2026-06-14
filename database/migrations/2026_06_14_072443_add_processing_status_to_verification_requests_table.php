<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_requests', function (Blueprint $table) {
            $table->string('processing_status')->default('pending')->after('id');
        });

        // Backfill existing rows as completed (they were all run synchronously)
        DB::table('verification_requests')->update(['processing_status' => 'completed']);
    }

    public function down(): void
    {
        Schema::table('verification_requests', function (Blueprint $table) {
            $table->dropColumn('processing_status');
        });
    }
};
