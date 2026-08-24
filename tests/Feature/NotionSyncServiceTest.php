<?php

namespace Tests\Feature;

use App\Jobs\SyncNotionContentJob;
use App\Models\LessonTutorGuide;
use App\Services\Notion\NotionSyncService;
use App\Support\Markdown\TutorGuideMarkdownRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
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
        $previousImage = $guide
            ->addMediaFromString('old-image-bytes')
            ->usingFileName('old-image.png')
            ->toMediaCollection('intro-images');
        $previousImagePath = $previousImage->getPathRelativeToRoot();
        $previousImageDirectory = dirname($previousImagePath);
        $previousImageDirectoryMarkerPath = "{$previousImageDirectory}/.folder-marker";

        Storage::disk('bunny')->put($previousImageDirectoryMarkerPath, '');

        SyncNotionContentJob::dispatchSync($guide);

        $guide->refresh();

        $this->assertNotNull($guide->last_synced_at);
        $this->assertTrue($guide->last_synced_at->greaterThan(now()->subMinute()));

        $markdown = $guide->introMarkdown();

        $this->assertStringContainsString('Hello world', $markdown);
        $this->assertStringContainsString('Click **Marketplace** in your sidebar', $markdown);
        $this->assertStringContainsString('- [x] Completed setup', $markdown);
        $this->assertStringContainsString('- [ ] Review with student', $markdown);
        $this->assertStringContainsString("```php\n<?php echo 1;\n```", $markdown);
        // Encoded as a safe fenced code block, never as raw <iframe> HTML
        // in the stored markdown — see YouTubeEmbedFencedCodeRenderer.
        $this->assertStringContainsString("```youtube-embed\ndQw4w9WgXcQ\n```", $markdown);
        $this->assertStringContainsString("![](/lesson-tutor-guides/{$guide->id}/media/", $markdown);
        $this->assertStringNotContainsString('** Marketplace**', $markdown);

        // The image must be re-hosted on our own disk — never linked
        // straight back to Notion's expiring temporary URL.
        $this->assertStringNotContainsString('notion-temp.example.com', $markdown);

        $this->assertModelMissing($previousImage);
        Storage::disk('bunny')->assertMissing($previousImagePath);
        Storage::disk('bunny')->assertMissing($previousImageDirectoryMarkerPath);
        $this->assertCount(1, $guide->fresh()->getMedia('intro-images'));
    }

    public function test_unsupported_block_types_are_tracked_as_skipped_not_silently_lost(): void
    {
        Http::fake([
            'api.notion.com/v1/blocks/*/children*' => Http::response([
                'object' => 'list',
                'results' => [
                    [
                        'object' => 'block',
                        'id' => 'block-1',
                        'type' => 'paragraph',
                        'has_children' => false,
                        'paragraph' => ['rich_text' => [
                            ['type' => 'text', 'plain_text' => 'Supported', 'annotations' => [], 'href' => null],
                        ]],
                    ],
                    [
                        'object' => 'block',
                        'id' => 'block-2',
                        'type' => 'callout',
                        'has_children' => false,
                    ],
                ],
                'has_more' => false,
                'next_cursor' => null,
            ]),
        ]);

        $guide = LessonTutorGuide::factory()->create();

        $result = app(NotionSyncService::class)->fetchPageAsMarkdown('83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e', $guide);

        $this->assertSame(['callout'], $result->skippedBlockTypes);
        $this->assertStringContainsString('Supported', $result->markdown);
    }

    public function test_a_notion_table_is_converted_to_a_markdown_table_not_skipped(): void
    {
        // fetchBlockChildrenRecursive issues a separate HTTP call per
        // has_children block, so the table's rows must be faked as their
        // own /blocks/{table-block}/children response — not nested
        // directly under the parent response, which the real API never
        // does either.
        Http::fake([
            'api.notion.com/v1/blocks/83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e/children*' => Http::response([
                'object' => 'list',
                'results' => [
                    [
                        'object' => 'block',
                        'id' => 'table-block',
                        'type' => 'table',
                        'has_children' => true,
                        'table' => ['table_width' => 2, 'has_column_header' => true, 'has_row_header' => false],
                    ],
                ],
                'has_more' => false,
                'next_cursor' => null,
            ]),
            'api.notion.com/v1/blocks/table-block/children*' => Http::response([
                'object' => 'list',
                'results' => [
                    [
                        'object' => 'block',
                        'id' => 'row-1',
                        'type' => 'table_row',
                        'has_children' => false,
                        'table_row' => ['cells' => [
                            [['type' => 'text', 'plain_text' => 'Name', 'annotations' => [], 'href' => null]],
                            [['type' => 'text', 'plain_text' => 'Role', 'annotations' => [], 'href' => null]],
                        ]],
                    ],
                    [
                        'object' => 'block',
                        'id' => 'row-2',
                        'type' => 'table_row',
                        'has_children' => false,
                        'table_row' => ['cells' => [
                            [['type' => 'text', 'plain_text' => 'Ada', 'annotations' => [], 'href' => null]],
                            [['type' => 'text', 'plain_text' => 'A | B', 'annotations' => [], 'href' => null]],
                        ]],
                    ],
                ],
                'has_more' => false,
                'next_cursor' => null,
            ]),
        ]);

        $guide = LessonTutorGuide::factory()->create();

        $result = app(NotionSyncService::class)->fetchPageAsMarkdown('83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e', $guide);

        $this->assertSame([], $result->skippedBlockTypes);
        $this->assertSame(
            "| Name | Role |\n| --- | --- |\n| Ada | A \\| B |",
            $result->markdown,
        );

        $html = app(TutorGuideMarkdownRenderer::class)->render($result->markdown);

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<th>Name</th>', $html);
        $this->assertStringContainsString('<td>A | B</td>', $html);
    }

    public function test_the_sync_job_returns_skipped_block_types_for_the_filament_action(): void
    {
        Storage::fake('local');
        Storage::fake('bunny');

        Http::fake([
            'api.notion.com/v1/blocks/*/children*' => Http::response([
                'object' => 'list',
                'results' => [
                    ['object' => 'block', 'id' => 'block-1', 'type' => 'callout', 'has_children' => false],
                ],
                'has_more' => false,
                'next_cursor' => null,
            ]),
        ]);

        $guide = LessonTutorGuide::factory()->create(['notion_page_id' => '83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e']);

        // Mirrors how EditLesson's "Sync from Notion" action invokes the
        // job, so its handle() return value (skipped block types) is
        // actually available to build the notification from.
        $skippedBlockTypes = Bus::dispatchNow(new SyncNotionContentJob($guide));

        $this->assertSame(['callout'], $skippedBlockTypes);
    }

    public function test_literal_markdown_syntax_in_notion_text_does_not_get_reinterpreted(): void
    {
        Http::fake([
            'api.notion.com/v1/blocks/*/children*' => Http::response([
                'object' => 'list',
                'results' => [
                    [
                        'object' => 'block',
                        'id' => 'block-1',
                        'type' => 'paragraph',
                        'has_children' => false,
                        'paragraph' => ['rich_text' => [
                            [
                                'type' => 'text',
                                'plain_text' => 'Use **bold** carefully',
                                'annotations' => ['bold' => false, 'italic' => false, 'code' => false],
                                'href' => null,
                            ],
                        ]],
                    ],
                ],
                'has_more' => false,
                'next_cursor' => null,
            ]),
        ]);

        $guide = LessonTutorGuide::factory()->create();

        $result = app(NotionSyncService::class)->fetchPageAsMarkdown('83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e', $guide);
        $html = app(TutorGuideMarkdownRenderer::class)->render($result->markdown);

        $this->assertStringNotContainsString('<strong>', $html);
        $this->assertStringContainsString('Use **bold** carefully', $html);
    }

    public function test_raw_html_typed_as_notion_text_is_escaped_not_executed(): void
    {
        Http::fake([
            'api.notion.com/v1/blocks/*/children*' => Http::response([
                'object' => 'list',
                'results' => [
                    [
                        'object' => 'block',
                        'id' => 'block-1',
                        'type' => 'paragraph',
                        'has_children' => false,
                        'paragraph' => ['rich_text' => [
                            [
                                'type' => 'text',
                                'plain_text' => 'Hello <img src=x onerror="alert(1)">',
                                'annotations' => ['bold' => false, 'italic' => false, 'code' => false],
                                'href' => null,
                            ],
                        ]],
                    ],
                ],
                'has_more' => false,
                'next_cursor' => null,
            ]),
        ]);

        $guide = LessonTutorGuide::factory()->create();

        $result = app(NotionSyncService::class)->fetchPageAsMarkdown('83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e', $guide);
        $html = app(TutorGuideMarkdownRenderer::class)->render($result->markdown);

        $this->assertStringNotContainsString('<img src=x onerror', $html);
        $this->assertStringContainsString('&lt;img src=x onerror="alert(1)"&gt;', $html);
    }

    public function test_multiple_notion_hosted_images_each_resolve_to_a_distinct_url(): void
    {
        Storage::fake('local');
        Storage::fake('bunny');

        Http::fake([
            'api.notion.com/v1/blocks/*/children*' => Http::response([
                'object' => 'list',
                'results' => [
                    [
                        'object' => 'block',
                        'id' => 'image-block-1',
                        'type' => 'image',
                        'has_children' => false,
                        'image' => ['type' => 'file', 'file' => ['url' => 'https://notion-temp.example.com/one.png'], 'caption' => []],
                    ],
                    [
                        'object' => 'block',
                        'id' => 'image-block-2',
                        'type' => 'image',
                        'has_children' => false,
                        'image' => ['type' => 'file', 'file' => ['url' => 'https://notion-temp.example.com/two.png'], 'caption' => []],
                    ],
                ],
                'has_more' => false,
                'next_cursor' => null,
            ]),
            'notion-temp.example.com/*' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/png']),
        ]);

        $guide = LessonTutorGuide::factory()->create();

        $result = app(NotionSyncService::class)->fetchPageAsMarkdown('83f2c4b1a2e94b0f9c6d3e5a7b8c9d0e', $guide);

        $this->assertCount(2, $guide->fresh()->getMedia('intro-images'));

        preg_match_all('/!\[]\(([^)]+)\)/', $result->markdown, $matches);

        $this->assertCount(2, $matches[1]);
        $this->assertNotSame($matches[1][0], $matches[1][1]);
        $this->assertStringNotContainsString('notion-temp.example.com', $matches[1][0]);
        $this->assertStringNotContainsString('notion-temp.example.com', $matches[1][1]);
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
                    'type' => 'to_do',
                    'has_children' => false,
                    'to_do' => [
                        'checked' => true,
                        'rich_text' => [
                            [
                                'type' => 'text',
                                'plain_text' => 'Completed setup',
                                'annotations' => ['bold' => false, 'italic' => false, 'code' => false],
                                'href' => null,
                            ],
                        ],
                    ],
                ],
                [
                    'object' => 'block',
                    'id' => 'block-4',
                    'type' => 'to_do',
                    'has_children' => false,
                    'to_do' => [
                        'checked' => false,
                        'rich_text' => [
                            [
                                'type' => 'text',
                                'plain_text' => 'Review with student',
                                'annotations' => ['bold' => false, 'italic' => false, 'code' => false],
                                'href' => null,
                            ],
                        ],
                    ],
                ],
                [
                    'object' => 'block',
                    'id' => 'block-5',
                    'type' => 'embed',
                    'has_children' => false,
                    'embed' => [
                        'url' => 'https://youtu.be/dQw4w9WgXcQ?si=test',
                        'caption' => [],
                    ],
                ],
                [
                    'object' => 'block',
                    'id' => 'block-6',
                    'type' => 'code',
                    'has_children' => false,
                    'code' => [
                        'language' => 'php',
                        'rich_text' => [
                            [
                                'type' => 'text',
                                'plain_text' => '<?php echo 1;',
                                'annotations' => ['bold' => false, 'italic' => false, 'code' => false],
                                'href' => null,
                            ],
                        ],
                    ],
                ],
                [
                    'object' => 'block',
                    'id' => 'block-7',
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
