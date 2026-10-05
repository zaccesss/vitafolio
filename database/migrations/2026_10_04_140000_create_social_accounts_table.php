<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** sign-in with google, github, microsoft or linkedin; an account can connect several */
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 20);
            // the provider's own id for the person, which never changes even if their email does
            $table->string('provider_id', 191);
            $table->string('email', 254)->nullable();
            // kept so the owner can choose to copy it as their photo; never shown from the provider
            $table->string('avatar_url', 500)->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_id']);
            $table->unique(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
