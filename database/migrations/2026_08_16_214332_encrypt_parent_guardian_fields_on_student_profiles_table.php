<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Widen before encrypting: ciphertext for a short plaintext value
        // already runs ~200+ chars (envelope + base64 overhead), so the
        // existing varchar(255) columns would truncate/reject real values.
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->text('parent_name')->nullable()->change();
            $table->text('parent_email')->nullable()->change();
            $table->text('parent_phone')->nullable()->change();
        });

        DB::table('student_profiles')
            ->where(fn ($query) => $query
                ->whereNotNull('parent_name')
                ->orWhereNotNull('parent_email')
                ->orWhereNotNull('parent_phone'))
            ->orderBy('id')
            ->chunkById(100, function ($profiles) {
                foreach ($profiles as $profile) {
                    DB::table('student_profiles')->where('id', $profile->id)->update([
                        'parent_name' => $profile->parent_name !== null ? Crypt::encryptString($profile->parent_name) : null,
                        'parent_email' => $profile->parent_email !== null ? Crypt::encryptString($profile->parent_email) : null,
                        'parent_phone' => $profile->parent_phone !== null ? Crypt::encryptString($profile->parent_phone) : null,
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('student_profiles')
            ->where(fn ($query) => $query
                ->whereNotNull('parent_name')
                ->orWhereNotNull('parent_email')
                ->orWhereNotNull('parent_phone'))
            ->orderBy('id')
            ->chunkById(100, function ($profiles) {
                foreach ($profiles as $profile) {
                    DB::table('student_profiles')->where('id', $profile->id)->update([
                        'parent_name' => $profile->parent_name !== null ? Crypt::decryptString($profile->parent_name) : null,
                        'parent_email' => $profile->parent_email !== null ? Crypt::decryptString($profile->parent_email) : null,
                        'parent_phone' => $profile->parent_phone !== null ? Crypt::decryptString($profile->parent_phone) : null,
                    ]);
                }
            });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('parent_name')->nullable()->change();
            $table->string('parent_email')->nullable()->change();
            $table->string('parent_phone')->nullable()->change();
        });
    }
};
