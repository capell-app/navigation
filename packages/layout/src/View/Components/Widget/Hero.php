<?php

declare(strict_types=1);

namespace Capell\Layout\View\Components\Widget;

use Capell\Frontend\Facades\FrontendLoader;
use Illuminate\Database\Eloquent\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Hero extends AbstractWidget
{
    protected ?Media $backgroundImage = null;

    protected ?Collection $contents = null;

    protected static string $defaultView = 'capell-layout::components.widget.hero.index';

    public function render(array $data = [])
    {
        return parent::render([
            ...$data,
            'backgroundImage' => $this->backgroundImage,
        ]);
    }

    protected function mountWidget(): void
    {
        $page = FrontendLoader::getPage();

        $this->backgroundImage = $page->translation?->backgroundImage ?? $this->widget->backgroundImage;

        if (! $this->backgroundImage instanceof Media &&
            ! $this->widget->translation?->content &&
            empty($page->translation->meta['hero']) &&
            $this->widget->assets->isEmpty()
        ) {
            $this->skipRender = true;
        }
    }
}
