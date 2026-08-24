<?php

namespace Tests\Feature;

use App\Jobs\SyncNotionContentJob;
use App\Models\LessonTutorGuide;
use App\Services\Notion\NotionSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NotionSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function notionUrlProvider(): array
    {
        return [
            'notion.so with a title slug' => [
                'https://www.notion.so/My-Page-Title-83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e',
                '83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e',
            ],
            'notion.so with a workspace prefix segment' => [
                'https://www.notion.so/myworkspace/My-Page-Title-83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e',
                '83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e',
            ],
            'notion.so with just the bare id, no title slug' => [
                'https://www.notion.so/83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e',
                '83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e',
            ],
            'notion.so with a dashed uuid form' => [
                'https://www.notion.so/My-Page-83f2c4b1-a2e9-4b0f-9c6d-3e5a7b8c9d0e',
                '83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e',
            ],
            'notion.site custom domain' => [
                'https://my-workspace.notion.site/My-Page-83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e',
                '83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e',
            ],
            'app.notion.com copied page link' => [
                'https://app.notion.com/p/Welcome-to-Notion-3c668ba0d26f801cbefbc7b864826a2f?source=copy_link',
                '3c668ba0d26f801cbefbc7b864826a2f',
            ],
            'trailing query string' => [
                'https://www.notion.so/My-Page-Title-83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e?pvs=4',
                '83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e',
            ],
        ];
    }

    #[DataProvider('notionUrlProvider')]
    public function test_extract_page_id_handles_various_notion_url_formats(string $url, string $expectedId): void
    {
        $service = app(NotionSyncService::class);

        $this->assertSame($expectedId, $service->extractPageId($url));
    }

    public function test_extract_page_id_returns_null_for_a_non_notion_shaped_url(): void
    {
        $service = app(NotionSyncService::class);

        $this->assertNull($service->extractPageId('https://example.com/not-a-page'));
    }

    public function test_a_sync_writes_markdown_to_storage_and_updates_last_synced_at(): void
    {
        Storage::fake('local');
        Storage::fake('bunny');

        Http::fake([
            'api.notion.com/v1/blocks/*/children*' => Http::response($this->fakeNotionBlocksResponse()),
            'notion-temp.example.com/*' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/png']),
        ]);

        $guide = LessonTutorGuide::factory()->create([
            'notion_page_id' => '83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e',
            'last_synced_at' => null,
        ]);

        SyncNotionContentJob::dispatchSync($guide);

        $guide->refresh();

        $this->assertNotNull($guide->last_synced_at);
        $this->assertTrue($guide->last_synced_at->greaterThan(now()->subMinute()));

        $markdown = $guide->introMarkdown();

        $this->assertStringContainsString('Hello world', $markdown);
        $this->assertStringContainsString('Click **Marketplace** in your sidebar', $markdown);
        $this->assertStringContainsString("![](/lesson-tutor-guides/{$guide->id}/media/", $markdown);
        $this->assertStringNotContainsString('** Marketplace**', $markdown);

        // The image must be re-hosted on our own disk — never linked
        // straight back to Notion's expiring temporary URL.
        $this->assertStringNotContainsString('notion-temp.example.com', $markdown);
    }

    /**
     * @return array<string, mixed>
     */
    private function fakeNotionBlocksResponse(): array
    {
        return [
            'object' => 'list',
            'results' => [
                [
                    'object' => 'block',
                    'id' => 'block-1',
                    'type' => 'paragraph',
                    'has_children' => false,
                    'paragraph' => [
                        'rich_text' => [
                            [
                                'type' => 'text',
                                'plain_text' => 'Hello world',
                                'annotations' => ['bold' => false, 'italic' => false, 'code' => false],
                                'href' => null,
                            ],
                        ],
                    ],
                ],
                [
                    'object' => 'block',
                    'id' => 'block-2',
                    'type' => 'paragraph',
                    'has_children' => false,
                    'paragraph' => [
                        'rich_text' => [
                            [
                                'type' => 'text',
                                'plain_text' => 'Click',
                                'annotations' => ['bold' => false, 'italic' => false, 'code' => false],
                                'href' => null,
                            ],
                            [
                                'type' => 'text',
                                'plain_text' => ' Marketplace',
                                'annotations' => ['bold' => true, 'italic' => false, 'code' => false],
                                'href' => null,
                            ],
                            [
                                'type' => 'text',
                                'plain_text' => ' in your sidebar',
                                'annotations' => ['bold' => false, 'italic' => false, 'code' => false],
                                'href' => null,
                            ],
                        ],
                    ],
                ],
                [
                    'object' => 'block',
                    'id' => 'block-3',
                    'type' => 'image',
                    'has_children' => false,
                    'image' => [
                        'type' => 'file',
                        'file' => ['url' => 'https://notion-temp.example.com/image.png'],
                        'caption' => [],
                    ],
                ],
            ],
            'has_more' => false,
            'next_cursor' => null,
        ];
    }
}
