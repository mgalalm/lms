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
        Schema::create('parent_contact_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_contact_id')->constrained()->cascadeOnDelete();
            // 'email' or 'phone' — one table for both channels rather than
            // two, disambiguated by this column.
            $table->string('channel');
            $table->string('code_hash');
            // The exact value the code was sent to, encrypted — kept so a
            // rapid contact-point change mid-flow can't verify against the
            // wrong destination.
            $table->text('destination');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['parent_contact_id', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_contact_verifications');
    }
};
