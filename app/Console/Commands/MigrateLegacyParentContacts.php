<?php

namespace App\Console\Commands;

use App\Models\ParentContact;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * One-time migration of legacy parent/guardian data from
 * student_profiles.{parent_name,parent_email,parent_phone,consent_given_at}
 * into the new parent_contacts table. Deliberately a command, not a
 * migration up() — it needs to decrypt/re-encrypt PII, be safely
 * re-runnable, and must NOT let ParentContactObserver blast a
 * verification code to every legacy row at once.
 *
 * Run this before the drop_parent_guardian_fields_from_student_profiles_table
 * migration, which refuses to run if it finds data this command hasn't
 * migrated yet.
 */
class MigrateLegacyParentContacts extends Command
{
    protected $signature = 'parent-contacts:migrate-legacy {--dry-run : Preview without writing}';

    protected $description = 'Migrate legacy parent/guardian data from student_profiles into parent_contacts';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $rows = DB::table('student_profiles')
            ->where(function ($query) {
                $query->whereNotNull('parent_name')
                    ->orWhereNotNull('parent_email')
                    ->orWhereNotNull('parent_phone')
                    ->orWhereNotNull('consent_given_at');
            })
            ->get();

        $migrated = 0;
        $skipped = 0;
        $failed = [];

        foreach ($rows as $row) {
            if (ParentContact::where('student_id', $row->user_id)->exists()) {
                $this->warn("Student #{$row->user_id} already has a parent_contacts row — skipped.");
                $skipped++;

                continue;
            }

            try {
                $name = $row->parent_name !== null ? Crypt::decryptString($row->parent_name) : null;
                $email = $row->parent_email !== null ? Crypt::decryptString($row->parent_email) : null;
                $phone = $row->parent_phone !== null ? Crypt::decryptString($row->parent_phone) : null;
            } catch (DecryptException $e) {
                $this->error("Student #{$row->user_id}: failed to decrypt legacy parent data — {$e->getMessage()}");
                $failed[] = $row->user_id;

                continue;
            }

            if ($dryRun) {
                $this->line("Would migrate student #{$row->user_id}: name=".($name !== null ? 'set' : 'null').', email='.($email !== null ? 'set' : 'null').', phone='.($phone !== null ? 'set' : 'null'));
                $migrated++;

                continue;
            }

            $contact = ParentContact::withoutEvents(function () use ($row, $name, $email, $phone) {
                return ParentContact::create([
                    'student_id' => $row->user_id,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    // Migrated contact points are unverified until they
                    // pass through the normal verification flow.
                    'email_verified_at' => null,
                    'phone_verified_at' => null,
                    'consent_given_at' => $row->consent_given_at,
                ]);
            });

            // The observer that would normally compute these (and seed
            // preferences) was suppressed above, so do both explicitly.
            $contact->forceFill([
                'email_hash' => $email ? hash_hmac('sha256', $email, config('app.key')) : null,
                'phone_hash' => $phone ? hash_hmac('sha256', $phone, config('app.key')) : null,
            ])->save();

            ParentContact::seedDefaultPreferences($contact);

            $migrated++;
        }

        $this->newLine();
        $this->table(
            ['Migrated', 'Skipped (already migrated)', 'Failed to decrypt'],
            [[$migrated, $skipped, count($failed)]],
        );

        if ($failed !== []) {
            $this->error('Failed student profile user_ids: '.implode(', ', $failed));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
