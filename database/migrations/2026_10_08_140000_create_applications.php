<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // set when saved from the Jobs page; the copied fields below keep the record after the listing closes
            $table->foreignId('job_listing_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 200);
            $table->string('company', 160)->nullable();
            $table->string('location', 160)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('kind', 20)->nullable();
            $table->string('status', 20)->default('saved')->index();
            $table->date('applied_on')->nullable();
            $table->date('deadline')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'job_listing_id']);
        });

        // apply clicks per listing per day: a count only, never who clicked
        Schema::create('job_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_listing_id')->constrained()->cascadeOnDelete();
            $table->date('clicked_on');
            $table->unsignedInteger('clicks')->default(0);
            $table->unique(['job_listing_id', 'clicked_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_clicks');
        Schema::dropIfExists('applications');
    }
};
