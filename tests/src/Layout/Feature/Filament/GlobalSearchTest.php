<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Layout\Filament\Resources\Contents\ContentResource;
use Capell\Layout\Filament\Resources\Widgets\WidgetResource;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResult;

uses(CreatesAdminUser::class)
    ->group('global-search');

beforeEach(function (): void {
    test()->actingAsAdmin();

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    Filament::setServingStatus();
});

it('finds content by every globally searchable content attribute', function (): void {
    $contentNameToken = 'capell-layout-content-name-token';
    $contentTitleToken = 'capell-layout-content-title-token';

    $language = Language::factory()->create();

    $content = Content::factory()->create([
        'name' => $contentNameToken,
    ]);

    $content->translations()->create([
        'language_id' => $language->id,
        'title' => $contentTitleToken,
    ]);

    $resultsByName = Filament::getGlobalSearchProvider()->getResults($contentNameToken);
    $resultsByTranslationTitle = Filament::getGlobalSearchProvider()->getResults($contentTitleToken);

    $contentResultByName = $resultsByName?->getCategories()->get(ContentResource::getPluralModelLabel())?->first();
    $contentResultByTranslationTitle = $resultsByTranslationTitle?->getCategories()->get(ContentResource::getPluralModelLabel())?->first();

    expect($contentResultByName)
        ->toBeInstanceOf(GlobalSearchResult::class)
        ->and($contentResultByName->title)->toBe($content->name)
        ->and($contentResultByName->url)->toBe(ContentResource::getUrl('edit', ['record' => $content]))
        ->and($contentResultByTranslationTitle)->toBeInstanceOf(GlobalSearchResult::class)
        ->and($contentResultByTranslationTitle->title)->toBe($content->name)
        ->and($contentResultByTranslationTitle->url)->toBe(ContentResource::getUrl('edit', ['record' => $content]));
});

it('finds a widget by every globally searchable widget attribute', function (): void {
    $widgetNameToken = 'capell-layout-widget-name-token';
    $widgetKeyToken = 'capell-layout-widget-key-token';
    $widgetTitleToken = 'capell-layout-widget-title-token';
    $widgetComponentToken = 'capell-layout-widget-component-token';
    $widgetFileToken = 'capell-layout-widget-file-token';
    $widgetComponentItemToken = 'capell-layout-widget-component-item-token';

    $language = Language::factory()->create();

    $widget = Widget::factory()->create([
        'name' => $widgetNameToken,
        'key' => $widgetKeyToken,
        'meta' => [
            'component' => $widgetComponentToken,
            'file' => $widgetFileToken,
            'component_item' => $widgetComponentItemToken,
        ],
    ]);

    $widget->translations()->create([
        'language_id' => $language->id,
        'title' => $widgetTitleToken,
    ]);

    $searchTerms = [
        $widgetNameToken,
        $widgetKeyToken,
        $widgetTitleToken,
        $widgetComponentToken,
        $widgetFileToken,
        $widgetComponentItemToken,
    ];

    foreach ($searchTerms as $searchTerm) {
        $results = Filament::getGlobalSearchProvider()->getResults($searchTerm);
        $widgetResult = $results?->getCategories()->get(WidgetResource::getPluralModelLabel())?->first();

        expect($widgetResult)
            ->toBeInstanceOf(GlobalSearchResult::class)
            ->and($widgetResult->title)->toBe($widget->name)
            ->and($widgetResult->url)->toBe(WidgetResource::getUrl('edit', ['record' => $widget]));
    }
});
