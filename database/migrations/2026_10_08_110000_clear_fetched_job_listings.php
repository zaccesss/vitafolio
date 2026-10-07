<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Board listings were stored once per advert id, so a role posted for several cities showed as
     * several cards. They are only a copy of the boards and the next fetch stores them again, one
     * per role.
     */
    public function up(): void
    {
        DB::table('job_listings')->whereIn('source', ['adzuna', 'reed'])->delete();
    }

    public function down(): void {}
};
