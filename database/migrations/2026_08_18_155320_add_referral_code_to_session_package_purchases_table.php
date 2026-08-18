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
        Schema::table('session_package_purchases', function (Blueprint $table) {
            // Captured at checkout time (before payment), redeemed once
            // the purchase is actually marked paid — see
            // SessionPackagePurchase::markAsPaid().
            $table->string('referral_code')->nullable()->after('stripe_checkout_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_package_purchases', function (Blueprint $table) {
            $table->dropColumn('referral_code');
        });
    }
};
