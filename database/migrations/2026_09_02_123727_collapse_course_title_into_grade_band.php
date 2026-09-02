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
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('title');
            $table->string('grade_band')->nullable(false)->change();
            $table->unsignedInteger('sort_order')->default(0)->after('grade_band');
            $table->unique('grade_band');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropUnique(['grade_band']);
            $table->dropColumn('sort_order');
            $table->string('grade_band')->nullable()->change();
            $table->string('title')->after('id');
        });
    }
};
