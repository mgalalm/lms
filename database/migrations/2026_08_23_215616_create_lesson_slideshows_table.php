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
        Schema::create('lesson_slideshows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('locale')->default('en');
            // Nullable: a PPTX upload creates the row before the queued
            // CloudConvert conversion finishes and fills this in. A direct
            // PDF upload sets it immediately, no pending state.
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->unique(['lesson_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_slideshows');
    }
};
