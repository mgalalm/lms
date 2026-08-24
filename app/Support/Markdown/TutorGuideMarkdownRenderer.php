<?php

namespace App\Support\Markdown;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\MarkdownConverter;

class TutorGuideMarkdownRenderer
{
    private readonly MarkdownConverter $converter;

    public function __construct(?MarkdownConverter $converter = null)
    {
        $this->converter = $converter ?? $this->makeConverter();
    }

    public function render(?string $markdown): ?string
    {
        if ($markdown === null || $markdown === '') {
            return null;
        }

        return $this->converter->convert($markdown)->getContent();
    }

    private function makeConverter(): MarkdownConverter
    {
        // html_input 'escape' matches HelpArticle's existing CommonMark
        // config: raw HTML typed into synced Notion content must never
        // reach the browser unescaped (see YouTubeEmbedFencedCodeRenderer
        // for how a real embed still renders safely despite this).
        $environment = new Environment(['html_input' => 'escape']);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TaskListExtension);
        $environment->addExtension(new TableExtension);
        $environment->addRenderer(
            FencedCode::class,
            new YouTubeEmbedFencedCodeRenderer(new HighlightedFencedCodeRenderer),
            100,
        );

        return new MarkdownConverter($environment);
    }
}
