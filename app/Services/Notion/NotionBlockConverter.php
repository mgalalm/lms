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
 * Supports: headings (h1-h3), paragraphs, bulleted/numbered lists, to-dos, code
 * blocks with language, images, YouTube embeds, tables, and bold/italic/inline-code/link
 * text annotations. Unsupported block types (toggles, callouts, ...) are
 * dropped from the output rather than erroring, since a real Notion page
 * will contain many block kinds outside this minimum scope — but they are
 * recorded and exposed via skippedBlockTypes(), so a lossy sync is never
 * reported as a plain, unqualified success.
 */
class NotionBlockConverter
{
    /** @var array<int, string> */
    private array $skippedBlockTypes = [];

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    public function convert(array $blocks): string
    {
        $this->skippedBlockTypes = [];

        return $this->convertBlocks($blocks, 0);
    }

    /**
     * Distinct Notion block types encountered during the last convert()
     * call that this converter doesn't support and silently dropped —
     * surfaced by SyncNotionContentJob so a sync that lost content is
     * never reported as a plain, unqualified success.
     *
     * @return array<int, string>
     */
    public function skippedBlockTypes(): array
    {
        return array_values(array_unique($this->skippedBlockTypes));
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
                'to_do' => $indent.'- ['.(($block['to_do']['checked'] ?? false) ? 'x' : ' ').'] '.$this->richTextToMarkdown($block['to_do']['rich_text'] ?? []),
                'code' => $this->convertCodeBlock($block),
                'image' => $this->convertImageBlock($block),
                'embed' => $this->convertYouTubeBlock($block['embed']['url'] ?? null),
                'video' => $this->convertYouTubeBlock($block['video']['external']['url'] ?? null),
                'table' => $this->convertTableBlock($block),
                default => $this->recordSkippedBlock($type),
            };

            if ($line !== null && $line !== '') {
                $lines[] = $line;
            }

            // A table's children (table_row blocks) are fully consumed by
            // convertTableBlock() above — recursing into them here too
            // would both duplicate the output and record a spurious
            // 'table_row' skipped-block entry (table_row isn't, and
            // shouldn't be, a top-level supported type on its own).
            if ($type !== 'table' && ! empty($block['children'])) {
                $childMarkdown = $this->convertBlocks($block['children'], $depth + 1);

                if ($childMarkdown !== '') {
                    $lines[] = $childMarkdown;
                }
            }
        }

        return implode("\n\n", $lines);
    }

    private function recordSkippedBlock(?string $type): ?string
    {
        if ($type !== null) {
            $this->skippedBlockTypes[] = $type;
        }

        return null;
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
     * Notion's table_row blocks arrive as $block['children'] (the generic
     * recursive block fetch in NotionSyncService already populates this,
     * same as any other block with has_children). Rendered as a GFM
     * markdown table via league/commonmark's TableExtension, registered
     * in TutorGuideMarkdownRenderer.
     *
     * Markdown tables have no syntax for a headerless table, so the first
     * row is always rendered as the header regardless of Notion's
     * has_column_header setting. has_row_header (first column styled as
     * a header) has no markdown-table equivalent and isn't represented.
     */
    private function convertTableBlock(array $block): string
    {
        $rows = array_values(array_filter(
            $block['children'] ?? [],
            fn (array $row) => ($row['type'] ?? null) === 'table_row',
        ));

        if ($rows === []) {
            return '';
        }

        $columnCount = count($rows[0]['table_row']['cells'] ?? []);
        $lines = [];

        foreach ($rows as $index => $row) {
            $lines[] = $this->tableRowMarkdown($row['table_row']['cells'] ?? []);

            if ($index === 0) {
                $lines[] = '| '.implode(' | ', array_fill(0, $columnCount, '---')).' |';
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, array<int, array<string, mixed>>>  $cells
     */
    private function tableRowMarkdown(array $cells): string
    {
        $cellText = array_map(
            // A literal '|' or newline in cell content would corrupt the
            // table's row structure, so it's neutralized here rather than
            // in the general markdown escaping (which only cares about
            // characters that are significant everywhere, not just
            // inside a table cell).
            fn (array $richText) => str_replace(['|', "\n"], ['\\|', ' '], $this->richTextToMarkdown($richText)),
            $cells,
        );

        return '| '.implode(' | ', $cellText).' |';
    }

    /**
     * Encoded as a ```youtube-embed fenced code block containing just the
     * validated video ID — never as raw <iframe> HTML in the markdown
     * source. TutorGuideMarkdownRenderer runs with html_input 'escape' (so
     * arbitrary raw HTML typed into Notion text can never reach the
     * browser unescaped) and relies on YouTubeEmbedFencedCodeRenderer to
     * turn this specific, fully-validated fenced block into a real embed.
     */
    private function convertYouTubeBlock(?string $url): string
    {
        $videoId = $this->youTubeVideoId($url);

        if ($videoId === null) {
            return '';
        }

        return "```youtube-embed\n{$videoId}\n```";
    }

    private function youTubeVideoId(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        $videoId = null;

        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $videoId = explode('/', trim($path, '/'))[0] ?? null;
        } elseif ($host === 'youtube.com' || str_ends_with($host, '.youtube.com')) {
            if ($path === '/watch') {
                parse_str($parts['query'] ?? '', $query);
                $videoId = $query['v'] ?? null;
            } elseif (preg_match('#^/(embed|shorts|live)/([^/?]+)#', $path, $matches)) {
                $videoId = $matches[2];
            }
        }

        if (! is_string($videoId) || ! preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) {
            return null;
        }

        return $videoId;
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

                if (! preg_match('/^(\s*)(.*?)(\s*)$/u', $text, $matches)) {
                    return $text;
                }

                $leadingWhitespace = $matches[1];
                $text = $matches[2];
                $trailingWhitespace = $matches[3];

                if ($text === '') {
                    return $leadingWhitespace.$trailingWhitespace;
                }

                $isCode = $annotations['code'] ?? false;

                // Escape markdown-significant characters in the literal
                // text itself, so e.g. a Notion paragraph that plainly
                // reads "Use **bold** carefully" isn't corrupted into
                // actual bold when re-parsed. Skipped for code spans,
                // since CommonMark doesn't interpret markdown inside them.
                if (! $isCode) {
                    $text = $this->escapeMarkdown($text);
                }

                if ($isCode) {
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

                return $leadingWhitespace.$text.$trailingWhitespace;
            })
            ->implode('');
    }

    private function escapeMarkdown(string $text): string
    {
        return preg_replace('/([\\\\`*_\[\]])/', '\\\\$1', $text);
    }
}
