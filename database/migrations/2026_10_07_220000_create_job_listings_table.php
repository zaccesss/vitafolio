<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20);
            $table->string('external_id', 64);
            $table->string('kind', 20)->index();
            $table->string('title', 200);
            $table->string('company', 160)->nullable();
            $table->string('location', 160)->nullable();
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->text('description')->nullable();
            $table->string('url', 500);
            $table->timestamp('posted_at')->nullable()->index();
            $table->timestamp('closes_at')->nullable();
            $table->timestamps();
            $table->unique(['source', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};
