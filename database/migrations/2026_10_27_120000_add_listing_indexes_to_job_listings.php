<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            // every Jobs page visit filters out closed roles, which read the whole table without this
            $table->index('closes_at');
            // a kind tab lists its newest roles first, so one index serves both the filter and the order
            $table->index(['kind', 'posted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropIndex(['closes_at']);
            $table->dropIndex(['kind', 'posted_at']);
        });
    }
};
