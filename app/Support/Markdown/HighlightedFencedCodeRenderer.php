<?php

namespace App\Support\Markdown;

use Highlight\Highlighter;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;
use Throwable;

class HighlightedFencedCodeRenderer implements NodeRendererInterface
{
    public function __construct(private readonly Highlighter $highlighter = new Highlighter) {}

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        FencedCode::assertInstanceOf($node);

        $language = $this->language($node);
        $codeAttributes = $node->data->getData('attributes');

        if ($language !== null) {
            $codeAttributes->append('class', 'language-'.$language);
        }

        $codeAttributes->append('class', 'hljs');

        return new HtmlElement(
            'pre',
            $language === null ? [] : ['data-language' => strtoupper($language)],
            new HtmlElement('code', $codeAttributes->export(), $this->highlight($node->getLiteral(), $language)),
        );
    }

    private function highlight(string $code, ?string $language): string
    {
        try {
            return $language === null
                ? $this->highlighter->highlightAuto($code)->value
                : $this->highlighter->highlight($language, $code)->value;
        } catch (Throwable) {
            return Xml::escape($code);
        }
    }

    private function language(FencedCode $node): ?string
    {
        $infoWords = $node->getInfoWords();

        if ($infoWords === [] || $infoWords[0] === '') {
            return null;
        }

        return strtolower(str_starts_with($infoWords[0], 'language-')
            ? substr($infoWords[0], 9)
            : $infoWords[0]);
    }
}
