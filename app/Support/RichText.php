<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Sanitizes Tiptap-authored HTML before it's persisted, so a request crafted
 * outside the editor (e.g. a raw POST to the message endpoint) can't store
 * script tags or event-handler attributes that get replayed to other users
 * via v-html.
 */
class RichText
{
    /**
     * @var array<string, list<string>>
     */
    private const ALLOWED_TAGS = [
        'p' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'u' => [],
        's' => [],
        'strike' => [],
        'blockquote' => [],
        'pre' => [],
        'code' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'br' => [],
        'hr' => [],
        'h1' => [],
        'h2' => [],
        'h3' => [],
        'span' => ['style'],
    ];

    /**
     * Tags whose content (not just the tag itself) must never survive sanitization.
     */
    private const DROP_ENTIRELY = ['script', 'style', 'iframe', 'object', 'embed', 'link', 'meta'];

    public static function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8"?><div>'.$html.'</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();

        $container = $document->getElementsByTagName('div')->item(0);

        if (! $container) {
            return null;
        }

        self::sanitizeChildren($document, $container);

        $result = '';

        foreach (iterator_to_array($container->childNodes) as $child) {
            $result .= $document->saveHTML($child);
        }

        return trim($result) === '' ? null : $result;
    }

    private static function sanitizeChildren(DOMDocument $document, DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMText) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                $node->removeChild($child);

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_ENTIRELY, true)) {
                $node->removeChild($child);

                continue;
            }

            if (! array_key_exists($tag, self::ALLOWED_TAGS)) {
                self::sanitizeChildren($document, $child);

                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }

                $node->removeChild($child);

                continue;
            }

            foreach (iterator_to_array($child->attributes ?? []) as $attribute) {
                if (! in_array($attribute->nodeName, self::ALLOWED_TAGS[$tag], true)) {
                    $child->removeAttribute($attribute->nodeName);

                    continue;
                }

                if ($attribute->nodeName === 'style') {
                    $child->setAttribute('style', self::sanitizeStyle($attribute->nodeValue));
                }
            }

            self::sanitizeChildren($document, $child);
        }
    }

    /**
     * Only a text color declaration is allowed through (matches the Tiptap
     * Color extension's output); anything else in the style attribute is dropped.
     */
    private static function sanitizeStyle(string $style): string
    {
        if (preg_match('/color:\s*(#[0-9a-fA-F]{3,8}|rgb\([\d,\s]+\))/', $style, $matches)) {
            return 'color: '.$matches[1];
        }

        return '';
    }
}
