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
        Schema::table('lesson_tutor_guides', function (Blueprint $table) {
            $table->string('notion_page_id')->nullable()->after('locale');
            $table->timestamp('last_synced_at')->nullable()->after('notion_page_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_tutor_guides', function (Blueprint $table) {
            $table->dropColumn(['notion_page_id', 'last_synced_at']);
        });
    }
};
