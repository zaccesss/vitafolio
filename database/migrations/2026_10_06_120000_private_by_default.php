<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// a new cv starts private and a new profile unlisted, so nothing is listed before its owner chooses to publish.
// existing rows keep the visibility their owners set
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cvs', fn (Blueprint $table) => $table->string('visibility', 10)->default('private')->change());
        Schema::table('users', fn (Blueprint $table) => $table->string('profile_visibility', 10)->default('unlisted')->change());
    }

    public function down(): void
    {
        Schema::table('cvs', fn (Blueprint $table) => $table->string('visibility', 10)->default('public')->change());
        Schema::table('users', fn (Blueprint $table) => $table->string('profile_visibility', 10)->default('public')->change());
    }
};
