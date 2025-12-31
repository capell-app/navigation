<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Enums\WidgetComponentEnum;
use Capell\Layout\Models\Widget;
use Capell\Layout\Services\Creator\WidgetCreator;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;

uses(TestingFrontend::class);

it('creates page content widget with expected meta', function (): void {
    $creator = resolve(WidgetCreator::class);

    $widget = $creator->pageContentWidget();

    expect($widget)->toBeInstanceOf(Widget::class)
        ->and($widget->key)->toBe('page-content')
        ->and($widget->meta['component'] ?? null)->toBe(WidgetComponentEnum::PageContent->value)
        ->and($widget->meta['page_content'] ?? null)->toBe(['title', 'content']);
});

it('renders page content widget on page', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $creator = resolve(WidgetCreator::class);

    $widget = $creator->pageContentWidget();

    $layout = (new LayoutFactory)->widgets([$widget])->create();

    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('.widget-page-content', fn (AssertElement $elm) => $elm);
});
