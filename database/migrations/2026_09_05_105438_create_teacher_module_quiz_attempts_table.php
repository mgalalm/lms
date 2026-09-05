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
        Schema::create('teacher_module_quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')
                ->constrained(indexName: 'tmqa_teacher_profile_id_foreign')
                ->cascadeOnDelete();
            $table->foreignId('module_id')
                ->constrained(indexName: 'tmqa_module_id_foreign')
                ->cascadeOnDelete();
            // The questions drawn for this take. Each attempt samples the
            // module's banks fresh, so grading has to score against what
            // was actually shown rather than re-drawing.
            $table->json('question_ids');
            $table->unsignedTinyInteger('score')->nullable();
            $table->boolean('passed')->nullable();
            $table->timestamp('submitted_at')->nullable();
            // An admin reset voids attempts rather than deleting them —
            // this gates certification, so the history is worth keeping.
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();

            $table->index(['teacher_profile_id', 'module_id'], 'tmqa_teacher_module_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_module_quiz_attempts');
    }
};
