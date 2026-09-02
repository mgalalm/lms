<?php

namespace App\Console\Commands;

use App\Services\Zoom\ZoomClient;
use Illuminate\Console\Command;

/**
 * Disables the "Co-host" permission on the configured Zoom host account so
 * teachers can no longer promote a student to co-host during a live
 * session — a co-host gets the same in-meeting controls as the host.
 * Zoom's API has no field for the related "automatically grant co-owner
 * permissions to co-hosts, alternative hosts, and changed hosts to manage
 * meeting assets" setting; that one must still be disabled by hand in the
 * Zoom admin portal (Account Settings > Meeting > In Meeting (Advanced)).
 * Safe to re-run.
 */
class DisableZoomCoHostCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zoom:disable-co-host';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable the Co-host permission on the configured Zoom host account';

    /**
     * Execute the console command.
     */
    public function handle(ZoomClient $zoomClient): int
    {
        $zoomUserId = config('services.zoom.host_user_id');

        if (! $zoomUserId) {
            $this->error('No Zoom host user configured (services.zoom.host_user_id).');

            return self::FAILURE;
        }

        $zoomClient->disableCoHost($zoomUserId);

        $this->info("Co-host permission disabled for Zoom user {$zoomUserId}.");
        $this->warn('The "auto-grant co-owner permissions to manage meeting assets" setting has no API field — disable it by hand in the Zoom admin portal.');

        return self::SUCCESS;
    }
}
