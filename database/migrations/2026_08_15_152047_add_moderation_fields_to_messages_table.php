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
        Schema::table('messages', function (Blueprint $table) {
            $table->string('moderation_status')->default('pending')->after('attachments');
            $table->json('moderation_flags')->nullable()->after('moderation_status');
            $table->timestamp('moderated_at')->nullable()->after('moderation_flags');

            $table->index('moderation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['moderation_status']);
            $table->dropColumn(['moderation_status', 'moderation_flags', 'moderated_at']);
        });
    }
};
