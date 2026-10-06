<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// a cover letter lives on its cv row, so it always shares the cv's theme, address and visibility.
// both columns are nullable: an empty letter means the cv has none and its page stays hidden
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cvs', function (Blueprint $table) {
            $table->string('letter_to', 160)->nullable()->after('experience');
            $table->text('cover_letter')->nullable()->after('letter_to');
        });
    }

    public function down(): void
    {
        Schema::table('cvs', function (Blueprint $table) {
            $table->dropColumn(['letter_to', 'cover_letter']);
        });
    }
};
