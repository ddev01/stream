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
        Schema::create('twitch_user_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('twitch_user_id')->constrained('twitch_users')->cascadeOnDelete();
            $table->string('name');
            $table->bigInteger('value')->nullable();
            $table->timestamp('last_write')->nullable();
            $table->timestamps();

            $table->unique(['twitch_user_id', 'name']);
            $table->index('twitch_user_id');
            $table->index('name');
            $table->index(['name', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('twitch_user_stats');
    }
};
