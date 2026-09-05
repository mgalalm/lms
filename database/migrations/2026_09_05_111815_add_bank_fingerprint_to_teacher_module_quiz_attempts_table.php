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
        Schema::table('teacher_module_quiz_attempts', function (Blueprint $table) {
            // Fingerprint of the module's eligible question bank at the
            // moment this attempt was drawn. An open attempt is only
            // resumable while it still matches — if lessons or their
            // published quizzes have changed since, the draw is stale and
            // gets redrawn rather than examining content that has moved.
            $table->string('bank_fingerprint')->nullable()->after('question_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_module_quiz_attempts', function (Blueprint $table) {
            $table->dropColumn('bank_fingerprint');
        });
    }
};
