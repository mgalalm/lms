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
        Schema::create('lesson_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_quiz_id')->constrained()->cascadeOnDelete();
            $table->text('question_text');
            $table->string('type')->default('multiple_choice');
            $table->json('options');
            $table->string('correct_answer');
            $table->text('explanation')->nullable();
            $table->boolean('is_ai_generated')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_quiz_questions');
    }
};
