<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Zoom start_url embeds a long host-access (zak) token that
        // regularly exceeds the default varchar(255) limit and was
        // silently failing to save, leaving the Join button disabled.
        // SQLite (used in tests) has no fixed-length varchar constraint
        // to begin with, and doesn't support ALTER ... MODIFY syntax.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE class_sessions MODIFY zoom_join_url TEXT NULL');
        DB::statement('ALTER TABLE class_sessions MODIFY zoom_start_url TEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE class_sessions MODIFY zoom_join_url VARCHAR(255) NULL');
        DB::statement('ALTER TABLE class_sessions MODIFY zoom_start_url VARCHAR(255) NULL');
    }
};
