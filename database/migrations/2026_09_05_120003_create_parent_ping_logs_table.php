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
        Schema::create('parent_ping_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('triggered_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_contact_id')->nullable()->constrained()->nullOnDelete();
            // Null when no channel could be resolved (no_verified_contact).
            $table->string('channel')->nullable();
            $table->text('destination')->nullable();
            $table->string('status');
            $table->string('provider_message_id')->nullable();
            // Updated later by the Twilio status-callback webhook.
            $table->string('provider_status')->nullable();
            $table->text('message');
            $table->text('error_detail')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_ping_logs');
    }
};
