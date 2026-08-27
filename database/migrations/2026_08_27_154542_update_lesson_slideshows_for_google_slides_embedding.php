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
        Schema::table('lesson_slideshows', function (Blueprint $table) {
            $table->string('google_slides_url')->nullable()->after('locale');
            $table->string('google_slides_id')->nullable()->after('google_slides_url');
            $table->dropColumn('file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_slideshows', function (Blueprint $table) {
            $table->string('file_path')->nullable();
            $table->dropColumn(['google_slides_url', 'google_slides_id']);
        });
    }
};
