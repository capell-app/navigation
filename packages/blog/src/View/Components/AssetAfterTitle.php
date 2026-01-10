<?php

declare(strict_types=1);

namespace Capell\Blog\View\Components;

use Illuminate\View\Component;

class AssetAfterTitle extends Component
{
    public $publishDate;

    public $publishDatePosition;

    public $tags;

    public $publishDateOutput;

    public function __construct($publishDate = null, $publishDatePosition = null, $tags = null, $publishDateOutput = null)
    {
        $this->publishDate = $publishDate;
        $this->publishDatePosition = $publishDatePosition;
        $this->tags = $tags;
        $this->publishDateOutput = $publishDateOutput;
    }

    public function render()
    {
        if (
            (! $this->publishDate || $this->publishDatePosition !== 'bottom')
            && (empty($this->tags) || $this->tags->isEmpty())
        ) {
            return '';
        }

        return view('capell-blog::hooks.asset-after-title', [
            'publishDate' => $this->publishDate,
            'publishDatePosition' => $this->publishDatePosition,
            'tags' => $this->tags,
            'publishDateOutput' => $this->publishDateOutput,
        ]);
    }
}
