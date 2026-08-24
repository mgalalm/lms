<?php

namespace App\Jobs;

use App\Models\LessonTutorGuide;
use App\Services\Notion\NotionSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Pulls the linked Notion page's content into $lessonTutorGuide's own
 * storage. Used both by the daily re-sync command (dispatched async) and
 * by the Filament "Sync from Notion" action (dispatched sync, so the
 * admin gets an immediate success/failure notification).
 */
class SyncNotionContentJob implements ShouldQueue
{
    use Queueable;

    /**
     * Generous ceiling for the Notion round trip (recursive block fetch +
     * image downloads/re-uploads) — Horizon's global default
     * (config/horizon.php) is only 60 seconds, which would kill this job
     * mid-sync for a large page with several images.
     */
    public int $timeout = 900;

    public function __construct(public LessonTutorGuide $lessonTutorGuide)
    {
        //
    }

    public function handle(NotionSyncService $notion): void
    {
        if (! $this->lessonTutorGuide->notion_page_id) {
            throw new RuntimeException("LessonTutorGuide #{$this->lessonTutorGuide->id} has no notion_page_id to sync from.");
        }

        $previousIntroImages = $this->lessonTutorGuide->getMedia('intro-images');
        $previousIntroImageDirectories = $previousIntroImages
            ->map(fn ($media) => [
                'disk' => $media->disk,
                'directory' => dirname($media->getPathRelativeToRoot()),
            ])
            ->filter(fn (array $path) => $path['directory'] !== '.')
            ->unique(fn (array $path) => $path['disk'].'|'.$path['directory'])
            ->values();

        $markdown = $notion->fetchPageAsMarkdown($this->lessonTutorGuide->notion_page_id, $this->lessonTutorGuide);

        $this->lessonTutorGuide->updateIntroMarkdown($markdown);
        $this->lessonTutorGuide->update(['last_synced_at' => now()]);

        $previousIntroImages->each->delete();
        $previousIntroImageDirectories->each(function (array $path): void {
            Storage::disk($path['disk'])->deleteDirectory($path['directory']);
            Storage::disk($path['disk'])->delete($path['directory']);
        });
    }
}
