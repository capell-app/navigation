<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Enums\WidgetComponentEnum;
use Capell\Layout\Filament\Resources\Widgets\Schemas\Types\CarouselWidgetSchema;
use Capell\Layout\Models\Widget;
use Capell\Layout\Services\Creator\WidgetCreator;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;

uses(TestingFrontend::class);

it('creates media carousel widget with schema and component', function (): void {
    $creator = resolve(WidgetCreator::class);

    $widget = $creator->mediaCarouselWidget();

    expect($widget)->toBeInstanceOf(Widget::class)
        ->and($widget->key)->toBe('media-carousel')
        ->and($widget->meta['component'] ?? null)->toBe(WidgetComponentEnum::AssetCarousel->value)
        ->and($widget->admin['schema'] ?? null)->toBe(CarouselWidgetSchema::getKey());
});

it('renders media carousel widget on page', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $creator = resolve(WidgetCreator::class);

    $widget = $creator->mediaCarouselWidget();

    $layout = (new LayoutFactory)->widgets([$widget])->create();

    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('.widget-media-carousel', fn (AssertElement $elm) => $elm);
});
