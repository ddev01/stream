<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('shared_files');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Table structure was defined in earlier migrations (create_shared_files_table, add_expires_at_to_shared_files_table).
        // Re-run those migrations if rollback is needed.
    }
};
