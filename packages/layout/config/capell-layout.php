<?php

declare(strict_types=1);

return [
    'livewire_components' => [
        'capell.layout.livewire.layout-builder' => Capell\Layout\Livewire\LayoutBuilder::class,
        'capell-layout::layout-builder-assets-table-media' => Capell\Layout\Livewire\Assets\Table\MediaTable::class,
        'capell-layout::layout-builder-assets-table-page' => Capell\Layout\Livewire\Assets\Table\PagesTable::class,
        'capell-layout::layout-builder-assets-table-content' => Capell\Layout\Livewire\Assets\Table\ContentsTable::class,
        'capell::livewire.widget.pages' => Capell\Layout\Livewire\Widget\PagesWidget::class,
    ],

    'blade_components' => [
        'capell::widget.pages.related' => Capell\Layout\View\Components\Widget\Pages\RelatedWidget::class,
        'capell::widget.pages.children' => Capell\Layout\View\Components\Widget\Pages\ChildrenWidget::class,
        'capell::widget.pages.siblings' => Capell\Layout\View\Components\Widget\Pages\SiblingsWidget::class,
        'capell::widget.pages.latest' => Capell\Layout\View\Components\Widget\Pages\LatestWidget::class,
    ],
];
