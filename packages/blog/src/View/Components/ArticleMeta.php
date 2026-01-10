<?php

declare(strict_types=1);

namespace Capell\Blog\View\Components;

use Capell\Blog\Support\Loader\TagLoader;
use Capell\Core\Models\Page;
use Capell\Frontend\Facades\Frontend;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;

class ArticleMeta extends Component
{
    public ?Page $tagPage = null;

    public Collection $tags;

    public ?Model $author = null;

    public bool $withAuthor = false;

    public function __construct($withAuthor = false, $author = null)
    {
        $this->tags = TagLoader::getPageTags(Frontend::page());

        if ($this->tags->isEmpty()) {
            return;
        }

        $this->tagPage = TagLoader::getTagResultsPage(Frontend::site(), Frontend::language());

        if (! $this->tagPage) {
            throw new Exception('Tag results page not found for the current site and language.');
        }

        $this->author = $author;
        $this->withAuthor = $withAuthor;
    }

    public function render(): ?View
    {
        if ($this->tags->isEmpty() && ($this->withAuthor && ! $this->author)) {
            return null;
        }

        return view('capell-blog::components.article-meta', [
            'tagPage' => $this->tagPage,
            'tags' => $this->tags,
            'author' => $this->author,
            'withAuthor' => $this->withAuthor,
        ]);
    }
}
