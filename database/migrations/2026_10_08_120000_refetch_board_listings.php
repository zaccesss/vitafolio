<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Board listings are now matched on a cleaned employer name and filed by their title first, so
     * the stored copies are cleared and the next fetch stores them again under the new rules.
     */
    public function up(): void
    {
        DB::table('job_listings')->whereIn('source', ['adzuna', 'reed'])->delete();
    }

    public function down(): void {}
};
