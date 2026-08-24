<?php

namespace App\Services\Notion;

/**
 * Converts a Notion block tree (as returned by the Blocks API, with each
 * block's nested children already attached under a 'children' key) into
 * markdown. Deliberately pure/no HTTP — image URLs are expected to already
 * be resolved to our own storage under '_resolved_image_url' by
 * NotionSyncService before blocks reach here, since Notion's own image
 * URLs expire and must never be linked to directly.
 *
 * Supports: headings (h1-h3), paragraphs, bulleted/numbered lists, code
 * blocks with language, images, and bold/italic/inline-code/link text
 * annotations. Unsupported block types are silently skipped rather than
 * erroring, since a real Notion page will contain many block kinds
 * (toggles, callouts, tables, embeds, ...) outside this minimum scope.
 */
class NotionBlockConverter
{
    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    public function convert(array $blocks): string
    {
        return $this->convertBlocks($blocks, 0);
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    private function convertBlocks(array $blocks, int $depth): string
    {
        $lines = [];
        $numberedIndex = 0;

        foreach ($blocks as $block) {
            $type = $block['type'] ?? null;
            $indent = str_repeat('  ', $depth);

            if ($type !== 'numbered_list_item') {
                $numberedIndex = 0;
            }

            $line = match ($type) {
                'heading_1' => '# '.$this->richTextToMarkdown($block['heading_1']['rich_text'] ?? []),
                'heading_2' => '## '.$this->richTextToMarkdown($block['heading_2']['rich_text'] ?? []),
                'heading_3' => '### '.$this->richTextToMarkdown($block['heading_3']['rich_text'] ?? []),
                'paragraph' => $this->richTextToMarkdown($block['paragraph']['rich_text'] ?? []),
                'bulleted_list_item' => $indent.'- '.$this->richTextToMarkdown($block['bulleted_list_item']['rich_text'] ?? []),
                'numbered_list_item' => $indent.(++$numberedIndex).'. '.$this->richTextToMarkdown($block['numbered_list_item']['rich_text'] ?? []),
                'code' => $this->convertCodeBlock($block),
                'image' => $this->convertImageBlock($block),
                default => null,
            };

            if ($line !== null && $line !== '') {
                $lines[] = $line;
            }

            if (! empty($block['children'])) {
                $childMarkdown = $this->convertBlocks($block['children'], $depth + 1);

                if ($childMarkdown !== '') {
                    $lines[] = $childMarkdown;
                }
            }
        }

        return implode("\n\n", $lines);
    }

    private function convertCodeBlock(array $block): string
    {
        $code = $block['code'] ?? [];
        $language = $code['language'] ?? '';
        $text = $this->richTextToMarkdown($code['rich_text'] ?? []);

        return "```{$language}\n{$text}\n```";
    }

    private function convertImageBlock(array $block): string
    {
        $image = $block['image'] ?? [];
        $url = $block['_resolved_image_url'] ?? $image['file']['url'] ?? $image['external']['url'] ?? null;

        if (! $url) {
            return '';
        }

        $caption = $this->richTextToMarkdown($image['caption'] ?? []);

        return "![{$caption}]({$url})";
    }

    /**
     * @param  array<int, array<string, mixed>>  $richText
     */
    private function richTextToMarkdown(array $richText): string
    {
        return collect($richText)
            ->map(function (array $segment) {
                $text = $segment['plain_text'] ?? '';
                $annotations = $segment['annotations'] ?? [];

                if ($text === '') {
                    return '';
                }

                if ($annotations['code'] ?? false) {
                    $text = "`{$text}`";
                }

                if ($annotations['bold'] ?? false) {
                    $text = "**{$text}**";
                }

                if ($annotations['italic'] ?? false) {
                    $text = "_{$text}_";
                }

                $href = $segment['href'] ?? null;

                if ($href) {
                    $text = "[{$text}]({$href})";
                }

                return $text;
            })
            ->implode('');
    }
}
