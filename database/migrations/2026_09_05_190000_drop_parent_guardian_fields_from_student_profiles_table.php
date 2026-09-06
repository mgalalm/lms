<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Deliberately run well after 2026_09_05_120000_create_parent_contacts_table
     * and after `php artisan parent-contacts:migrate-legacy` has been run as
     * a deploy step (not automatic — see that command). Refuses to drop
     * anything if it finds parent data on student_profiles with no
     * corresponding parent_contacts row, rather than silently losing it.
     */
    public function up(): void
    {
        $orphaned = DB::table('student_profiles')
            ->where(function ($query) {
                $query->whereNotNull('parent_email')
                    ->orWhereNotNull('parent_phone')
                    ->orWhereNotNull('parent_name');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('parent_contacts')
                    ->whereColumn('parent_contacts.student_id', 'student_profiles.user_id');
            })
            ->count();

        if ($orphaned > 0) {
            throw new RuntimeException(
                "{$orphaned} student_profiles row(s) have parent/guardian data with no corresponding ".
                'parent_contacts row. Run `php artisan parent-contacts:migrate-legacy` first.'
            );
        }

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn(['parent_name', 'parent_email', 'parent_phone', 'consent_given_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('parent_name')->nullable()->after('timezone');
            $table->string('parent_email')->nullable()->after('parent_name');
            $table->string('parent_phone')->nullable()->after('parent_email');
            $table->timestamp('consent_given_at')->nullable()->after('parent_phone');
        });
    }
};
