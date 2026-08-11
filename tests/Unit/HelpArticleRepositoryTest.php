<?php

namespace Tests\Unit;

use App\Enums\HelpArticleAudience;
use App\Enums\UserRole;
use App\Models\User;
use App\Support\Help\HelpArticleRepository;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class HelpArticleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private string $fixturesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->fixturesPath = storage_path('framework/testing/help-articles-'.uniqid());
        File::makeDirectory($this->fixturesPath, recursive: true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->fixturesPath);

        parent::tearDown();
    }

    public function test_it_parses_frontmatter_and_body_from_markdown_files(): void
    {
        $this->writeArticle('joining.md', [
            'title' => 'How do I join?',
            'category' => 'Getting Started',
            'audience' => 'all',
            'order' => 2,
        ], 'Click **Join** on your dashboard.');

        $repository = new HelpArticleRepository($this->fixturesPath);
        $article = $repository->find('joining');

        $this->assertNotNull($article);
        $this->assertSame('How do I join?', $article->title);
        $this->assertSame('Getting Started', $article->category);
        $this->assertSame(HelpArticleAudience::All, $article->audience);
        $this->assertSame(2, $article->order);
        $this->assertStringContainsString('<strong>Join</strong>', $article->bodyHtml());
    }

    public function test_articles_are_sorted_by_category_then_order(): void
    {
        $this->writeArticle('b.md', ['title' => 'B', 'category' => 'Zebra', 'order' => 1], 'body');
        $this->writeArticle('a.md', ['title' => 'A', 'category' => 'Apple', 'order' => 2], 'body');
        $this->writeArticle('c.md', ['title' => 'C', 'category' => 'Apple', 'order' => 1], 'body');

        $repository = new HelpArticleRepository($this->fixturesPath);

        $this->assertSame(['C', 'A', 'B'], $repository->all()->pluck('title')->all());
    }

    public function test_visible_to_filters_by_role_and_always_includes_all_audience(): void
    {
        $this->writeArticle('everyone.md', ['title' => 'Everyone', 'category' => 'X', 'audience' => 'all'], 'body');
        $this->writeArticle('teacher-only.md', ['title' => 'Teachers', 'category' => 'X', 'audience' => 'teacher'], 'body');
        $this->writeArticle('student-only.md', ['title' => 'Students', 'category' => 'X', 'audience' => 'student'], 'body');

        $repository = new HelpArticleRepository($this->fixturesPath);
        $teacher = User::factory()->create();
        $teacher->assignRole(UserRole::Teacher->value);

        $titles = $repository->visibleTo($teacher)->pluck('title')->all();

        $this->assertContains('Everyone', $titles);
        $this->assertContains('Teachers', $titles);
        $this->assertNotContains('Students', $titles);
    }

    public function test_matches_checks_title_and_body_case_insensitively(): void
    {
        $this->writeArticle('zoom.md', ['title' => 'Joining Zoom', 'category' => 'X'], 'Open the Zoom app on your PHONE.');

        $repository = new HelpArticleRepository($this->fixturesPath);
        $article = $repository->find('zoom');

        $this->assertTrue($article->matches('zoom'));
        $this->assertTrue($article->matches('phone'));
        $this->assertFalse($article->matches('payroll'));
    }

    private function writeArticle(string $filename, array $meta, string $body): void
    {
        $yaml = collect($meta)->map(fn ($value, $key) => "{$key}: {$value}")->implode("\n");

        File::put("{$this->fixturesPath}/{$filename}", "---\n{$yaml}\n---\n{$body}\n");
    }
}
