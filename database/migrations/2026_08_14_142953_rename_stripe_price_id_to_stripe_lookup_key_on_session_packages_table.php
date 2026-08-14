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
        Schema::table('session_packages', function (Blueprint $table) {
            $table->renameColumn('stripe_price_id', 'stripe_lookup_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_packages', function (Blueprint $table) {
            $table->renameColumn('stripe_lookup_key', 'stripe_price_id');
        });
    }
};
