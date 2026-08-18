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
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('referrer_id')->constrained('users');
            $table->unsignedInteger('reward_sessions_referrer')->default(1);
            $table->unsignedInteger('reward_sessions_referee')->default(1);
            $table->unsignedInteger('uses_remaining')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Links a session-credit-ledger entry back to the referral code
        // that generated it, so both halves of a redemption (referrer's
        // bonus and referee's bonus) are traceable to the same code —
        // credits still flow entirely through the existing ledger.
        Schema::table('session_credit_ledger_entries', function (Blueprint $table) {
            $table->foreignId('referral_code_id')->nullable()->after('class_session_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_credit_ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referral_code_id');
        });

        Schema::dropIfExists('referral_codes');
    }
};
