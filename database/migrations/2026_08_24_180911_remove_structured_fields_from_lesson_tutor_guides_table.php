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
        Schema::table('lesson_tutor_guides', function (Blueprint $table) {
            if (Schema::hasColumn('lesson_tutor_guides', 'procedure_steps')) {
                $table->dropColumn('procedure_steps');
            }

            if (Schema::hasColumn('lesson_tutor_guides', 'glossary_terms')) {
                $table->dropColumn('glossary_terms');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lesson_tutor_guides', function (Blueprint $table) {
            if (! Schema::hasColumn('lesson_tutor_guides', 'procedure_steps')) {
                $table->json('procedure_steps')->nullable()->after('locale');
            }

            if (! Schema::hasColumn('lesson_tutor_guides', 'glossary_terms')) {
                $table->json('glossary_terms')->nullable()->after('procedure_steps');
            }
        });
    }
};
