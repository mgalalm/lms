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
        Schema::create('leave_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('monthly_limit')->default(5);
            $table->unsignedInteger('monthly_used')->default(0);
            $table->unsignedInteger('annual_limit')->default(15);
            $table->unsignedInteger('annual_used')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_credits');
    }
};
