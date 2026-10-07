<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            // the hiring system an employer's own listing came from, such as Greenhouse or Workday
            $table->string('board', 40)->nullable()->after('source');
            // the employer feed sends its whole current set each day; a listing it stops sending has closed
            $table->timestamp('last_seen_at')->nullable()->after('closes_at');
        });
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropColumn(['board', 'last_seen_at']);
        });
    }
};
