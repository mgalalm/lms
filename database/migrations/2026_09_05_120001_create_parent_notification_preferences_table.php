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
        Schema::create('parent_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_contact_id')->constrained()->cascadeOnDelete();
            $table->string('notification_type');
            $table->string('channel');
            $table->timestamps();

            // Laravel's auto-generated name for this index is 74 chars —
            // over MySQL's 64-char identifier limit (see
            // .ai/rules/migrations.md) — so an explicit short name is
            // required.
            $table->unique(
                ['parent_contact_id', 'notification_type'],
                'parent_notif_prefs_contact_type_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_notification_preferences');
    }
};
