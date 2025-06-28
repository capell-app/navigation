<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget;

use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Services\Loader\PageLoader;

class Breadcrumbs extends AbstractWidget
{
    protected string $defaultView = 'capell-layout::components.widget.breadcrumbs';

    protected array $pages = [];

    public function render(array $data = [])
    {
        $data['pages'] = $this->pages;

        return parent::render($data);
    }

    protected function mountWidget(): void
    {
        $page = Frontend::getPage();

        if (! $page instanceof \Capell\Core\Models\Page) {
            $this->skipRender = true;

            return;
        }

        $ancestors = PageLoader::getPageAncestors($page, Frontend::getLanguage(), Frontend::getSite());

        foreach ($ancestors as $ancestor) {
            $this->pages[] = $ancestor;
        }

        if ($this->pages === []) {
            $this->skipRender = true;
        }
    }
}
