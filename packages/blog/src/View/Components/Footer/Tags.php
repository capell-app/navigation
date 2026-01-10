<?php

declare(strict_types=1);

namespace Capell\Blog\View\Components\Footer;

use Capell\Blog\Support\Loader\TagLoader;
use Capell\Core\Models\Page;
use Capell\Frontend\Facades\Frontend;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Tags extends Component
{
    public array $item;

    public ?Page $tagPage = null;

    public Collection $tags;

    public function __construct(array $item)
    {
        $this->item = $item;

        $language = Frontend::language();
        $site = Frontend::site();

        $this->tags = TagLoader::getTags($site, $language, limit: 5, hasArticles: true);

        if ($this->tags->isEmpty()) {
            return;
        }

        $tagPage = TagLoader::getTagResultsPage($site, $language);
        if (! $tagPage) {
            return;
        }

        $this->tagPage = $tagPage;
    }

    public function render()
    {
        if ($this->tagPage === null || $this->tags->isEmpty()) {
            return '';
        }

        return view('capell-blog::components.footer.tags', [
            ...$this->item,
            'tagPage' => $this->tagPage,
            'tags' => $this->tags,
            'language' => Frontend::language(),
        ]);
    }
}
