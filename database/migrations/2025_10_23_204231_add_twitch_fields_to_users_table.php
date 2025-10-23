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
            $table->string('twitch_id')->unique();
            $table->string('twitch_login');
            $table->string('twitch_display_name');
            $table->string('twitch_profile_image_url')->nullable();
            $table->string('twitch_broadcaster_type')->nullable();
            $table->timestamp('twitch_created_at')->nullable();
            $table->text('twitch_description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'twitch_id',
                'twitch_login',
                'twitch_display_name',
                'twitch_profile_image_url',
                'twitch_broadcaster_type',
                'twitch_created_at',
                'twitch_description',
            ]);
        });
    }
};
