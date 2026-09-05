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
        Schema::create('teacher_lesson_quiz_attempts', function (Blueprint $table) {
            $table->id();
            // Short FK names: teacher_lesson_quiz_attempts is long enough
            // that Laravel's generated constraint names would exceed
            // MySQL's 64-char identifier limit.
            $table->foreignId('teacher_profile_id')
                ->constrained(indexName: 'tlqa_teacher_profile_id_foreign')
                ->cascadeOnDelete();
            $table->foreignId('lesson_quiz_id')
                ->constrained(indexName: 'tlqa_lesson_quiz_id_foreign')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->boolean('passed');
            // An admin reset voids prior attempts rather than deleting
            // them — this gates certification, so the history is worth
            // keeping. Only attempts with a null voided_at count.
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();

            $table->index(['teacher_profile_id', 'lesson_quiz_id'], 'tlqa_teacher_quiz_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_lesson_quiz_attempts');
    }
};
