<?php

namespace App\Support\Markdown;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

/**
 * NotionBlockConverter encodes a Notion video/embed block as a
 * ```youtube-embed fenced code block containing just the validated video
 * ID, rather than raw <iframe> HTML in the markdown source — that lets
 * TutorGuideMarkdownRenderer run with html_input 'escape' (arbitrary raw
 * HTML typed into synced Notion text must never reach the browser
 * unescaped) while still rendering a real embed for this one specific,
 * fully-validated case. Delegates to the normal code renderer for every
 * other fenced block.
 */
class YouTubeEmbedFencedCodeRenderer implements NodeRendererInterface
{
    public function __construct(private readonly NodeRendererInterface $codeRenderer) {}

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        FencedCode::assertInstanceOf($node);

        $videoId = trim($node->getLiteral());
        $language = $node->getInfoWords()[0] ?? null;

        if ($language !== 'youtube-embed' || ! preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) {
            return $this->codeRenderer->render($node, $childRenderer);
        }

        return new HtmlElement('iframe', [
            'src' => 'https://www.youtube.com/embed/'.$videoId,
            'title' => 'YouTube video',
            'allow' => 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share',
            'referrerpolicy' => 'strict-origin-when-cross-origin',
            'allowfullscreen' => true,
        ], '');
    }
}
