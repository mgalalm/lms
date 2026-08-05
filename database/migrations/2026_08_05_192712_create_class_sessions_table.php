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
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('online');
            $table->string('status')->default('scheduled');
            $table->dateTime('scheduled_at');
            $table->string('zoom_meeting_id')->nullable();
            $table->string('zoom_join_url')->nullable();
            $table->string('zoom_start_url')->nullable();
            $table->string('zoom_password')->nullable();
            $table->dateTime('teacher_joined_at')->nullable();
            $table->dateTime('student_joined_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->string('recap_path')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
