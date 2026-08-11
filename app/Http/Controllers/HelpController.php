<?php

namespace App\Http\Controllers;

use App\Support\Help\HelpArticle;
use App\Support\Help\HelpArticleRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    /**
     * List all topics, with no article selected.
     */
    public function index(Request $request, HelpArticleRepository $repository): Response
    {
        return $this->render($request, $repository, null);
    }

    /**
     * List all topics, with the given article open.
     */
    public function show(Request $request, HelpArticleRepository $repository, string $article): Response
    {
        return $this->render($request, $repository, $article);
    }

    private function render(Request $request, HelpArticleRepository $repository, ?string $slug): Response
    {
        $search = $request->string('search')->value();
        $visible = $repository->visibleTo($request->user());

        $selected = $slug ? $visible->firstWhere('slug', $slug) : null;
        abort_if($slug && ! $selected, 404);

        $listed = filled($search) ? $visible->filter(fn (HelpArticle $article) => $article->matches($search)) : $visible;

        return Inertia::render('help/Index', [
            'search' => $search,
            'categories' => $listed->groupBy('category')->map(fn ($group) => $group->map(fn (HelpArticle $article) => [
                'slug' => $article->slug,
                'title' => $article->title,
                'excerpt' => filled($search) ? $article->excerpt() : null,
            ])->values())->all(),
            'selected' => $selected ? [
                'slug' => $selected->slug,
                'title' => $selected->title,
                'category' => $selected->category,
                'body_html' => $selected->bodyHtml(),
            ] : null,
        ]);
    }
}
