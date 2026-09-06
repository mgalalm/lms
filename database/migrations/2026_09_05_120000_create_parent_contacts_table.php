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
        Schema::create('parent_contacts', function (Blueprint $table) {
            $table->id();
            // References users.id (the student), not student_profiles.id —
            // matches how ClassSession/ProjectSubmission key student_id.
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->text('name')->nullable();
            $table->text('email')->nullable();
            $table->text('phone')->nullable();
            // Blind-index columns for exact-match lookup only (the
            // "request a preferences link" flow) — email/phone above are
            // encrypted with a random IV per write, so a WHERE can never
            // match their ciphertext. These are HMAC-SHA256, not
            // encrypted, never displayed, and only ever written by
            // ParentContactObserver alongside the encrypted value they
            // index — never set directly.
            $table->string('email_hash', 64)->nullable()->index();
            $table->string('phone_hash', 64)->nullable()->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamp('consent_given_at')->nullable();
            $table->timestamps();
            // Deliberately no unique() on student_id — a student hasOne
            // parent contact today, but this leaves room for hasMany
            // later without restructuring.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_contacts');
    }
};
