<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop Laravel Pulse tables that were created by the package.
     */
    public function up(): void
    {
        Schema::dropIfExists('pulse_aggregates');
        Schema::dropIfExists('pulse_entries');
        Schema::dropIfExists('pulse_values');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tables would need to be recreated by installing laravel/pulse again
    }
};
