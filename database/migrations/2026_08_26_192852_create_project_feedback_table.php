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
        Schema::create('project_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_submission_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users');
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('reviewed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_feedback');
    }
};
