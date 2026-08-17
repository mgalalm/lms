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
        // already runs ~200+ chars, so the existing varchar(255) column
        // would truncate/reject real values.
        Schema::table('users', function (Blueprint $table) {
            $table->text('name')->change();
        });

        DB::table('users')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    DB::table('users')->where('id', $user->id)->update([
                        'name' => Crypt::encryptString($user->name),
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    DB::table('users')->where('id', $user->id)->update([
                        'name' => Crypt::decryptString($user->name),
                    ]);
                }
            });

        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->change();
        });
    }
};
