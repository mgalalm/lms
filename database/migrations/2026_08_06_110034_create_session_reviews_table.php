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
        Schema::create('session_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('star_rating')->default(3);
            $table->string('sentiment')->nullable();
            $table->string('flag_severity')->default('green');
            $table->unsignedTinyInteger('feedback_score')->default(0);
            $table->unsignedTinyInteger('attitude_score')->default(0);
            $table->unsignedTinyInteger('preparation_score')->default(0);
            $table->unsignedTinyInteger('curriculum_score')->default(0);
            $table->unsignedTinyInteger('teaching_score')->default(0);
            $table->unsignedTinyInteger('setup_score')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_reviews');
    }
};
