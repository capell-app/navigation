<?php

declare(strict_types=1);

namespace Capell\Hero\View\Components\Widget;

use Capell\Frontend\Facades\Frontend;
use Capell\Hero\Actions\HeroWidgetHasPrimaryHeadingAction;
use Capell\Layout\View\Components\Widget\AbstractWidget;
use Illuminate\Database\Eloquent\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Hero extends AbstractWidget
{
    protected ?Media $backgroundImage = null;

    protected ?Collection $contents = null;

    protected static string $defaultView = 'capell-hero::components.widget.hero';

    public function render(array $data = [])
    {
        return parent::render([
            ...$data,
            'backgroundImage' => $this->backgroundImage,
        ]);
    }

    protected function mountWidget(): void
    {
        $page = Frontend::page();

        $this->backgroundImage = $page->translation?->backgroundImage ?? $this->widget->backgroundImage;

        if (! $this->backgroundImage instanceof Media &&
            ! $this->widget->translation?->content &&
            empty($page->translation->meta['hero']) &&
            $this->widget->assets->isEmpty()
        ) {
            $this->skipRender = true;
        }

        HeroWidgetHasPrimaryHeadingAction::run($this->widget, $page);
    }
}
