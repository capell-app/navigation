<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Services\Creator\WidgetCreator;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;

uses(TestingFrontend::class);

// Minimal smoke tests that each widget renders a wrapper via GET when added to a page layout
// Blade-only components will have null class mapping but should still render via view name.

it('renders default widget wrapper', function (): void {
    $site = Site::factory()->withTranslations()->create();
    $widget = resolve(WidgetCreator::class)->pageContentWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('.widget-page-content', fn (AssertElement $elm) => $elm);
});

it('assets widget not visible when empty', function (): void {
    config()->set('capell-layout.widget.skip_render_empty', true);

    $site = Site::factory()->withTranslations()->create();
    $widget = resolve(WidgetCreator::class)->assetsWidget();
    $layout = (new LayoutFactory)->widgets([$widget])->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('main', fn (AssertElement $assert) => $assert->doesntContain('.widget-assets'));
});
