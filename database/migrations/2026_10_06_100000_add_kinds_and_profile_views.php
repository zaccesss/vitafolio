<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * cv counts gain a kind (a view, a pdf download, a file opened or a qr scan) and profiles get
     * their own counts. both keep the same rule: one per visitor per day, stored as a daily hash
     */
    public function up(): void
    {
        Schema::table('cv_views', function (Blueprint $table) {
            $table->string('kind', 8)->default('view')->after('cv_id');
            $table->unique(['cv_id', 'kind', 'viewed_on', 'visitor_hash'], 'cv_views_cv_kind_day_visitor_unique');
        });
        Schema::table('cv_views', function (Blueprint $table) {
            $table->dropUnique(['cv_id', 'viewed_on', 'visitor_hash']);
            $table->index(['cv_id', 'viewed_on']);
        });

        Schema::create('profile_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('viewed_on');
            $table->char('visitor_hash', 64);
            $table->string('referrer_host', 100)->nullable();
            $table->unique(['user_id', 'viewed_on', 'visitor_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_views');
        Schema::table('cv_views', function (Blueprint $table) {
            $table->unique(['cv_id', 'viewed_on', 'visitor_hash']);
        });
        Schema::table('cv_views', function (Blueprint $table) {
            $table->dropUnique('cv_views_cv_kind_day_visitor_unique');
            $table->dropIndex(['cv_id', 'viewed_on']);
            $table->dropColumn('kind');
        });
    }
};
