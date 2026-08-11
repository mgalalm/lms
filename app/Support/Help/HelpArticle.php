<?php

namespace App\Support\Help;

use App\Enums\HelpArticleAudience;
use Illuminate\Support\Str;
use League\CommonMark\CommonMarkConverter;

/**
 * A single help article, parsed from a markdown file with YAML frontmatter.
 */
class HelpArticle
{
    public function __construct(
        public string $slug,
        public string $title,
        public string $category,
        public HelpArticleAudience $audience,
        public int $order,
        public string $bodyMarkdown,
    ) {}

    /**
     * Render the article body as sanitized HTML.
     */
    public function bodyHtml(): string
    {
        static $converter;

        // Raw HTML in the markdown source is escaped rather than passed
        // through, so a bad edit to a content file can't inject markup.
        $converter ??= new CommonMarkConverter(['html_input' => 'escape']);

        return (string) $converter->convert($this->bodyMarkdown);
    }

    /**
     * A short plain-text excerpt, used for search result previews.
     */
    public function excerpt(int $length = 160): string
    {
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($this->bodyHtml())));

        return Str::limit($plain, $length);
    }

    /**
     * Whether this article's title or body contains the given search term.
     */
    public function matches(string $term): bool
    {
        return str_contains(Str::lower($this->title), Str::lower($term))
            || str_contains(Str::lower($this->bodyMarkdown), Str::lower($term));
    }
}
