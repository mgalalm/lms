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
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code')->unique()->nullable();
            $table->string('photo_path')->nullable();
            $table->string('rank')->nullable();
            $table->date('starting_date')->nullable();
            $table->json('languages')->nullable();
            $table->string('faculty_of_graduation')->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('tutor_team_id')->nullable();
            $table->string('team_leader_name')->nullable();
            $table->string('mentor_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('zoom_user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_profiles');
    }
};
