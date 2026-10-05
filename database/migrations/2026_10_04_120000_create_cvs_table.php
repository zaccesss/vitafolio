<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 80);
            $table->string('slug', 100)->unique();
            // optional; when empty the profile headline is used
            $table->string('headline', 120)->nullable();
            $table->string('key_language', 60)->nullable();
            $table->text('profile')->nullable();
            $table->text('education')->nullable();
            $table->text('experience')->nullable();
            // the order the owner dragged the sections into
            $table->json('section_order')->nullable();
            // source for the in-browser latex editor; the compiled pdf is stored as the cv file
            $table->mediumText('latex_source')->nullable();
            $table->string('visibility', 10)->default('public');
            $table->boolean('show_email')->default(false);
            $table->string('theme', 20)->default('classic');
            $table->string('accent', 20)->default('midnight');
            $table->string('font', 20)->default('sans');
            // set by a moderator; a hidden cv is visible to its owner only
            $table->timestamp('hidden_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();

            $table->index(['visibility', 'updated_at']);
        });

        // uploaded cv files sit in their own table so ordinary cv queries never load them
        Schema::create('cv_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('filename', 150);
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            // "upload" for a file the owner chose, "latex" for one compiled in the latex editor
            $table->string('source', 10)->default('upload');
            // files live on cloudinary when it is set up and in this table otherwise
            $table->string('storage', 12)->default('database');
            $table->string('public_id', 200)->nullable();
            $table->binary('data')->nullable();
            $table->timestamps();
        });
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement('ALTER TABLE cv_documents MODIFY data MEDIUMBLOB NULL');
        }

        // projects show proof of work; media sits on an image and video service, not in the database
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->string('url', 300)->nullable();
            $table->string('media_url', 500)->nullable();
            $table->string('media_type', 10)->nullable();
            $table->string('media_public_id', 200)->nullable();
            // counted towards the owner's storage allowance
            $table->unsignedInteger('media_size')->nullable();
            $table->string('media_alt', 200)->nullable();
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 30);
            $table->text('details')->nullable();
            $table->char('reporter_hash', 64);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['resolved_at', 'created_at']);
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->string('slug', 60)->unique();
        });

        Schema::create('cv_tag', function (Blueprint $table) {
            $table->foreignId('cv_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['cv_id', 'tag_id']);
        });

        Schema::create('cv_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_id')->constrained()->cascadeOnDelete();
            $table->date('viewed_on');
            $table->char('visitor_hash', 64);
            $table->string('referrer_host', 100)->nullable();
            // one row per visitor per cv per day keeps the counts honest
            $table->unique(['cv_id', 'viewed_on', 'visitor_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_views');
        Schema::dropIfExists('cv_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('cv_documents');
        Schema::dropIfExists('cvs');
    }
};
