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
        Schema::create('teacher_module_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')
                ->constrained(indexName: 'tmc_teacher_profile_id_foreign')
                ->cascadeOnDelete();
            $table->foreignId('module_id')
                ->constrained(indexName: 'tmc_module_id_foreign')
                ->cascadeOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->boolean('quiz_passed')->default(false);
            $table->timestamps();

            $table->unique(['teacher_profile_id', 'module_id'], 'tmc_teacher_module_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_module_completions');
    }
};
