<?php

declare(strict_types=1);

namespace Capell\Blog\View\Components\Page;

use Illuminate\Support\Collection;
use Illuminate\View\Component;

class BeforeContentTags extends Component
{
    public function __construct(public $item, public Collection $tags) {}

    public function render()
    {
        if (! $this->tags?->isNotEmpty()) {
            return '';
        }

        return view('capell-blog::page.tags', [
            'item' => $this->item,
            'tags' => $this->tags,
        ]);
    }
}
