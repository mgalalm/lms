<?php

namespace App\Support\Markdown;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
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
        $environment = new Environment;
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addRenderer(FencedCode::class, new HighlightedFencedCodeRenderer, 100);

        return new MarkdownConverter($environment);
    }
}
