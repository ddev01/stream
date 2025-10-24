<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('twitch_users', function (Blueprint $table) {
            $table->id();
            $table->string('twitch_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Basic fields from users.dat
            $table->string('display_name')->nullable();

            // OAuth-enriched fields (filled on sign-in)
            $table->string('profile_image_url')->nullable();
            $table->string('broadcaster_type')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('twitch_created_at')->nullable();
            $table->string('email')->nullable();

            $table->timestamps();

            $table->index('twitch_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('twitch_users');
    }
};
