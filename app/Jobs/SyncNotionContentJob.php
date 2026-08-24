<?php

namespace App\Jobs;

use App\Models\LessonTutorGuide;
use App\Services\Notion\NotionSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
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

    /**
     * @return array<int, string> Notion block types skipped during this
     *                            sync because they aren't supported —
     *                            returned so the Filament "Sync from
     *                            Notion" action (which runs this via
     *                            Bus::dispatchNow(), see EditLesson) can
     *                            surface a non-empty result in its
     *                            notification instead of a blanket
     *                            "success".
     */
    public function handle(NotionSyncService $notion): array
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

        $result = $notion->fetchPageAsMarkdown($this->lessonTutorGuide->notion_page_id, $this->lessonTutorGuide);

        $this->lessonTutorGuide->updateIntroMarkdown($result->markdown);
        $this->lessonTutorGuide->update(['last_synced_at' => now()]);

        $previousIntroImages->each->delete();
        $previousIntroImageDirectories->each(
            fn (array $path) => Storage::disk($path['disk'])->deleteDirectory($path['directory'])
        );

        if ($result->skippedBlockTypes !== []) {
            Log::warning('Notion sync skipped unsupported block types', [
                'lesson_tutor_guide_id' => $this->lessonTutorGuide->id,
                'skipped_block_types' => $result->skippedBlockTypes,
            ]);
        }

        return $result->skippedBlockTypes;
    }
}
