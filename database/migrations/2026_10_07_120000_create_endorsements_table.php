<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// an endorsement belongs to one cv and one endorser. both keys cascade, so deleting the cv
// or either account removes it. the unique pair keeps it to one per endorser per cv
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('endorsements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_id')->constrained()->cascadeOnDelete();
            $table->foreignId('endorser_id')->constrained('users')->cascadeOnDelete();
            $table->string('relationship', 30);
            $table->string('context', 120)->nullable();
            $table->text('body');
            // pending until the cv's owner approves it; hidden when they choose not to show it
            $table->string('status', 10)->default('pending');
            $table->timestamp('approved_at')->nullable();
            // set by a moderator, apart from the owner's choice, so the owner cannot undo it
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();
            $table->unique(['cv_id', 'endorser_id']);
            $table->index(['cv_id', 'status']);
        });

        // a report can now point at one endorsement on a cv rather than the cv as a whole
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('endorsement_id')->nullable()->after('cv_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('endorsement_id');
        });
        Schema::dropIfExists('endorsements');
    }
};
