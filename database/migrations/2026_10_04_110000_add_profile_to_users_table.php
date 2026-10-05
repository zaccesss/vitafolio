<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // the profile is who someone is, shared by every cv they keep
        Schema::table('users', function (Blueprint $table) {
            $table->string('handle', 40)->nullable()->unique()->after('name');
            $table->timestamp('handle_changed_at')->nullable();
            $table->string('profile_visibility', 10)->default('public');
            $table->string('pronouns', 30)->nullable();
            $table->string('headline', 120)->nullable();
            $table->text('bio')->nullable();
            $table->string('location', 100)->nullable();
            $table->string('university', 100)->nullable()->index();
            $table->string('availability', 20)->default('none');
            $table->text('links')->nullable();
            // the photo lives in the database because the host's disk is wiped on every restart
            $table->binary('avatar')->nullable();
            $table->string('avatar_type', 20)->nullable();
            $table->string('avatar_version', 20)->nullable();
            $table->string('role', 10)->default('member');
            // a suspended account cannot sign in and its cvs drop out of public view
            $table->timestamp('suspended_at')->nullable();
            // false for accounts made through google, github and the rest until a password is set
            $table->boolean('has_password')->default(true);
        });

        // an old handle redirects to the new one and cannot be claimed by anyone else for a month
        Schema::create('handle_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('handle', 40)->index();
            $table->timestamp('released_at');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement('ALTER TABLE users MODIFY avatar MEDIUMBLOB NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('handle_history');
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['handle']);
            $table->dropIndex(['university']);
            $table->dropColumn(['handle', 'handle_changed_at', 'profile_visibility', 'pronouns', 'headline', 'bio', 'location', 'university', 'availability', 'links', 'avatar', 'avatar_type', 'avatar_version', 'role', 'suspended_at', 'has_password']);
        });
    }
};
