<?php

namespace App\Console\Commands;

use App\Jobs\SyncNotionContentJob;
use App\Models\LessonTutorGuide;
use Illuminate\Console\Command;

/**
 * Re-syncs every tutor guide linked to a Notion page, so edits made in
 * Notion don't go stale. Scheduled daily in routes/console.php.
 */
class SyncNotionTutorGuidesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notion:sync-tutor-guides';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-sync all lesson tutor guides linked to a Notion page';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $guides = LessonTutorGuide::whereNotNull('notion_page_id')->get();

        foreach ($guides as $guide) {
            SyncNotionContentJob::dispatch($guide);
        }

        $this->info("Dispatched Notion sync for {$guides->count()} tutor guide(s).");

        return self::SUCCESS;
    }
}
