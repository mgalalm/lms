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
        Schema::create('project_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('live_url')->nullable();
            $table->string('status')->default('submitted');
            $table->timestamp('submitted_at');
            $table->timestamps();

            // A student may submit multiple attempts over time for the
            // same project, so this is not unique — just an index for
            // "this student's attempts on this project" lookups.
            $table->index(['lesson_project_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_submissions');
    }
};
