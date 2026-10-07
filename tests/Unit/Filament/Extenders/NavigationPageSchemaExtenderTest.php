<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Filament\Extenders\NavigationPageSchemaExtender;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

it('adds the navigation tab to page schemas', function (): void {
    $tabs = [Tab::make('existing')];
    $extended = new NavigationPageSchemaExtender()->extendTabs(Schema::make()->model(Page::class), $tabs);

    expect($extended)->toHaveCount(2)
        ->and($extended[0])->toBe($tabs[0]);
});

it('preserves tabs for models outside the page hierarchy', function (): void {
    $tabs = [Tab::make('existing')];
    $extended = new NavigationPageSchemaExtender()->extendTabs(Schema::make()->model(Site::class), $tabs);

    expect($extended)->toBe($tabs);
});

it('preserves tabs when a schema has no page model', function (): void {
    $tabs = [Tab::make('existing')];
    $extended = new NavigationPageSchemaExtender()->extendTabs(Schema::make(), $tabs);

    expect($extended)->toBe($tabs);
});
