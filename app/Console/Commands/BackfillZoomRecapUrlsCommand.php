<?php

namespace App\Console\Commands;

use App\Models\ClassSession;
use App\Services\Zoom\ZoomClient;
use Illuminate\Console\Command;
use Throwable;

/**
 * One-off backfill for sessions whose zoom_recap_url was never captured
 * — either their recording.completed webhook fired before zoom_recap_url
 * existed, or (for the handful that briefly succeeded under the old
 * local-download approach) it was never captured in the first place.
 * Safe to re-run: only ever touches sessions still missing a recap URL.
 */
class BackfillZoomRecapUrlsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zoom:backfill-recap-urls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Backfill zoom_recap_url for past sessions by querying Zoom's recordings API directly";

    /**
     * Execute the console command.
     */
    public function handle(ZoomClient $zoomClient): int
    {
        $sessions = ClassSession::query()
            ->whereNotNull('zoom_meeting_id')
            ->whereNull('zoom_recap_url')
            ->get();

        $this->info("Checking {$sessions->count()} session(s) with no recap URL yet...");

        $backfilled = 0;
        $unavailable = 0;
        $failed = 0;

        foreach ($sessions as $session) {
            try {
                $playUrl = $zoomClient->findRecapUrl($session->zoom_meeting_id);
            } catch (Throwable $e) {
                report($e);
                $failed++;

                continue;
            }

            if ($playUrl === null) {
                $unavailable++;

                continue;
            }

            $session->update(['zoom_recap_url' => $playUrl]);
            $backfilled++;
        }

        $this->info("Backfilled {$backfilled}. Unavailable on Zoom (never recorded or aged out of retention): {$unavailable}. Failed: {$failed}.");

        return self::SUCCESS;
    }
}
