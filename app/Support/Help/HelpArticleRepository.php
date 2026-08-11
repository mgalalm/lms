<?php

namespace App\Support\Help;

use App\Enums\HelpArticleAudience;
use App\Models\User;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads help articles from flat markdown files (with YAML frontmatter) in
 * resources/help — content lives in git, not the database, so it's edited
 * like code rather than through an admin panel.
 */
class HelpArticleRepository
{
    private readonly string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? resource_path('help');
    }

    /**
     * @return Collection<int, HelpArticle>
     */
    public function all(): Collection
    {
        if (! is_dir($this->path)) {
            return collect();
        }

        return collect(glob("{$this->path}/*.md"))
            ->map(fn (string $file) => $this->parse($file))
            ->sortBy([['category', 'asc'], ['order', 'asc'], ['title', 'asc']])
            ->values();
    }

    public function find(string $slug): ?HelpArticle
    {
        return $this->all()->firstWhere('slug', $slug);
    }

    /**
     * @return Collection<int, HelpArticle>
     */
    public function visibleTo(User $user): Collection
    {
        $audiences = collect([HelpArticleAudience::All->value])
            ->merge($user->getRoleNames())
            ->all();

        return $this->all()->filter(fn (HelpArticle $article) => in_array($article->audience->value, $audiences, true))->values();
    }

    private function parse(string $file): HelpArticle
    {
        $slug = pathinfo($file, PATHINFO_FILENAME);
        $contents = file_get_contents($file);

        if (! preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/s', $contents, $matches)) {
            throw new RuntimeException("Help article [{$slug}] is missing YAML frontmatter.");
        }

        $meta = Yaml::parse($matches[1]);

        return new HelpArticle(
            slug: $slug,
            title: $meta['title'],
            category: $meta['category'],
            audience: HelpArticleAudience::from($meta['audience'] ?? 'all'),
            order: (int) ($meta['order'] ?? 0),
            bodyMarkdown: trim($matches[2]),
        );
    }
}
