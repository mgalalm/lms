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
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('parent_name')->nullable()->after('timezone');
            $table->string('parent_email')->nullable()->after('parent_name');
            $table->string('parent_phone')->nullable()->after('parent_email');
            $table->timestamp('consent_given_at')->nullable()->after('parent_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn(['parent_name', 'parent_email', 'parent_phone', 'consent_given_at']);
        });
    }
};
