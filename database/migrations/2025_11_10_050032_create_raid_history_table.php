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
        Schema::create('raid_history', function (Blueprint $table) {
            $table->id();
            $table->string('oid')->unique()->comment('Original ObjectId from _id.$oid');
            $table->string('user_id')->comment('Twitch user ID who initiated the raid');
            $table->unsignedInteger('viewers')->comment('Number of viewers in the raid');
            $table->timestamp('timestamp')->comment('Raid timestamp from timestamp.$date');
            $table->timestamps();

            $table->foreign('user_id')->references('twitch_id')->on('twitch_users')->onDelete('restrict');

            $table->index('oid');
            $table->index('user_id');
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raid_history');
    }
};
