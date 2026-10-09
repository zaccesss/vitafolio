<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // a contact or cv message whose email failed, held only until a retry delivers it or it expires
        Schema::create('pending_messages', function (Blueprint $table) {
            $table->id();
            // null for the site's own inbox; deleting the cv drops messages addressed to its owner
            $table->foreignId('cv_id')->nullable()->constrained()->cascadeOnDelete();
            // sender name, address and message, encrypted at rest
            $table->text('payload');
            $table->unsignedSmallInteger('attempts')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_messages');
    }
};
