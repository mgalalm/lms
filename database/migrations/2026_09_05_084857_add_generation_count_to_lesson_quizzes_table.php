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
        Schema::table('lesson_quizzes', function (Blueprint $table) {
            // Counts every successful AI generation, including the first.
            // Capped by the quiz.max_generations setting; never reset, so
            // the limit is permanent per quiz.
            $table->unsignedInteger('generation_count')->default(0)->after('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_quizzes', function (Blueprint $table) {
            $table->dropColumn('generation_count');
        });
    }
};
