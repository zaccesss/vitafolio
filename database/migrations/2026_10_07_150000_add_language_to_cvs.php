<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cvs', function (Blueprint $table) {
            // the language of the cv's own labels, its pdf and its word file. separate from the
            // interface language, so a visitor reading the site in another language sees the cv as written
            $table->string('language', 8)->default('en')->after('font');
        });
        // existing cvs follow the language their owner already picked, where there is one
        DB::table('cvs')->whereIn('user_id', DB::table('users')->whereNotNull('locale')->select('id'))->orderBy('id')->each(function ($cv) {
            $locale = DB::table('users')->where('id', $cv->user_id)->value('locale');
            DB::table('cvs')->where('id', $cv->id)->update(['language' => $locale]);
        });
    }

    public function down(): void
    {
        Schema::table('cvs', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
};
