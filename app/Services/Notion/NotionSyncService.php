<?php

namespace App\Services\Notion;

use App\Models\LessonTutorGuide;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Pulls a Notion page's content into our own storage via Notion's official
 * REST API (no third-party SDK — none was already present in this
 * codebase). Content is never linked to or embedded from Notion directly:
 * markdown is fully converted and images are re-hosted on our own Bunny
 * disk, since Notion's own image URLs expire.
 */
class NotionSyncService
{
    private const API_BASE_URL = 'https://api.notion.com/v1';

    public function __construct(private readonly NotionBlockConverter $converter = new NotionBlockConverter) {}

    /**
     * Parse the 32-char page ID from a pasted Notion URL. Handles both
     * notion.so and notion.site (custom-domain/published) formats, with
     * or without a title slug, and with or without dashes in the ID.
     * Returns null if no ID-shaped segment is found — callers validate
     * the host separately (see the Filament form field's rule).
     */
    public function extractPageId(string $notionUrl): ?string
    {
        $path = parse_url(trim($notionUrl), PHP_URL_PATH);

        if (! $path) {
            return null;
        }

        $lastSegment = collect(explode('/', trim($path, '/')))->last();

        if (! $lastSegment) {
            return null;
        }

        $pattern = '/([0-9a-fA-F]{8}-?[0-9a-fA-F]{4}-?[0-9a-fA-F]{4}-?[0-9a-fA-F]{4}-?[0-9a-fA-F]{12})$/';

        if (! preg_match($pattern, $lastSegment, $matches)) {
            return null;
        }

        return strtolower(str_replace('-', '', $matches[1]));
    }

    /**
     * Fetch a Notion page's full block tree and convert it to markdown.
     * Any Notion-hosted images encountered are downloaded and re-uploaded
     * to $lessonTutorGuide's 'intro-images' media collection so the
     * resulting markdown never references Notion's (expiring) URLs.
     */
    public function fetchPageAsMarkdown(string $pageId, LessonTutorGuide $lessonTutorGuide): NotionSyncResult
    {
        $blocks = $this->fetchBlockChildrenRecursive($pageId);
        $blocks = $this->resolveImages($blocks, $lessonTutorGuide);

        $markdown = $this->converter->convert($blocks);

        return new NotionSyncResult($markdown, $this->converter->skippedBlockTypes());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchBlockChildrenRecursive(string $blockId): array
    {
        $blocks = [];
        $cursor = null;

        do {
            $response = $this->request()
                ->get(self::API_BASE_URL."/blocks/{$blockId}/children", array_filter([
                    'page_size' => 100,
                    'start_cursor' => $cursor,
                ]))
                ->throw();

            foreach ($response->json('results', []) as $block) {
                if ($block['has_children'] ?? false) {
                    $block['children'] = $this->fetchBlockChildrenRecursive($block['id']);
                }

                $blocks[] = $block;
            }

            $cursor = $response->json('next_cursor');
        } while ($response->json('has_more', false));

        return $blocks;
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array<string, mixed>>
     */
    private function resolveImages(array $blocks, LessonTutorGuide $lessonTutorGuide): array
    {
        $downloads = $this->collectNotionHostedImageUrls($blocks);

        // Notion-hosted image bytes are fetched concurrently rather than
        // one blocking Http::get() per image in sequence — a page with
        // many images would otherwise make total sync time scale linearly
        // with image count.
        $responses = $downloads === [] ? [] : Http::pool(
            fn (Pool $pool) => collect($downloads)
                ->map(fn (string $url, string $blockId) => $pool->as($blockId)->get($url))
                ->all()
        );

        return $this->applyResolvedImageUrls($blocks, $responses, $lessonTutorGuide);
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<string, string> block id => temporary Notion file URL
     */
    private function collectNotionHostedImageUrls(array $blocks): array
    {
        $urls = [];

        foreach ($blocks as $block) {
            $image = $block['image'] ?? [];

            if (($block['type'] ?? null) === 'image' && ($image['type'] ?? null) === 'file' && filled($image['file']['url'] ?? null)) {
                $urls[$block['id']] = $image['file']['url'];
            }

            if (! empty($block['children'])) {
                $urls += $this->collectNotionHostedImageUrls($block['children']);
            }
        }

        return $urls;
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @param  array<string, Response|Throwable>  $downloadedResponses
     * @return array<int, array<string, mixed>>
     */
    private function applyResolvedImageUrls(array $blocks, array $downloadedResponses, LessonTutorGuide $lessonTutorGuide): array
    {
        foreach ($blocks as &$block) {
            if (($block['type'] ?? null) === 'image') {
                $block['_resolved_image_url'] = $this->resolveImageUrl($block, $downloadedResponses, $lessonTutorGuide);
            }

            if (! empty($block['children'])) {
                $block['children'] = $this->applyResolvedImageUrls($block['children'], $downloadedResponses, $lessonTutorGuide);
            }
        }

        return $blocks;
    }

    /**
     * @param  array<string, Response|Throwable>  $downloadedResponses
     */
    private function resolveImageUrl(array $block, array $downloadedResponses, LessonTutorGuide $lessonTutorGuide): ?string
    {
        $image = $block['image'] ?? [];

        // Only Notion-hosted files expire and need re-hosting — an
        // externally-linked image is already a stable, non-Notion URL.
        if (($image['type'] ?? null) !== 'file') {
            return $image['external']['url'] ?? null;
        }

        $response = $downloadedResponses[$block['id']] ?? null;

        if ($response === null) {
            return null;
        }

        if ($response instanceof Throwable) {
            throw $response;
        }

        $media = $lessonTutorGuide
            ->addMediaFromString($response->throw()->body())
            ->usingFileName($block['id'].'.'.$this->guessExtension($image['file']['url'] ?? ''))
            ->toMediaCollection('intro-images');

        return route('lesson-tutor-guides.media', [
            'lessonTutorGuide' => $lessonTutorGuide,
            'media' => $media,
        ], false);
    }

    private function guessExtension(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $extension !== '' ? $extension : 'png';
    }

    private function request(): PendingRequest
    {
        return Http::withToken((string) config('services.notion.api_key'))
            ->withHeaders(['Notion-Version' => (string) config('services.notion.api_version')]);
    }
}
