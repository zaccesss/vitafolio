<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            // null for a visitor without an account; they reach the ticket through the private link they are emailed.
            // deleting an account deletes its tickets with it
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('email', 254);
            $table->string('category', 20);
            $table->string('subject', 150);
            $table->string('status', 20)->default('open')->index();
            // only a hash is kept, so the database alone cannot open a visitor's ticket
            $table->string('token_hash', 64);
            $table->timestamp('last_activity_at')->index();
            $table->timestamps();
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('from_staff')->default(false);
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('support_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_message_id')->constrained()->cascadeOnDelete();
            // a private file on the image host, fetched by the app only for someone allowed to see the ticket
            $table->string('public_id');
            $table->string('extension', 8);
            $table->string('original_name', 150);
            $table->unsignedInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_attachments');
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_tickets');
    }
};
