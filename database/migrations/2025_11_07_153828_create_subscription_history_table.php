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
        Schema::create('subscription_history', function (Blueprint $table) {
            $table->id();
            $table->string('oid')->unique()->comment('Original ObjectId from _id.$oid');
            $table->string('user_id')->comment('Twitch user ID who received the subscription');
            $table->string('gifter_user_id')->nullable()->comment('ID of user who gifted the subscription');
            $table->timestamp('subscribed_at')->comment('Subscription date from subscribedAt.$date');
            $table->timestamps();

            $table->foreign('user_id')->references('twitch_id')->on('twitch_users')->onDelete('restrict');
            $table->foreign('gifter_user_id')->references('twitch_id')->on('twitch_users')->onDelete('restrict');

            $table->index('oid');
            $table->index('user_id');
            $table->index('gifter_user_id');
            $table->index('subscribed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_history');
    }
};
