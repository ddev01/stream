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
        Schema::table('users', function (Blueprint $table) {
            $table->string('twitch_id')->unique()->nullable();
            $table->string('twitch_username')->nullable();
            $table->string('twitch_display_name')->nullable();
            $table->string('twitch_avatar')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['twitch_id', 'twitch_username', 'twitch_display_name', 'twitch_avatar']);
        });
    }
};
