<?php

declare(strict_types=1);

use Capell\Core\Enums\ModelEnum as CoreModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Layout\Database\Factories\LayoutFactory;
use Capell\Layout\Services\Creator\WidgetCreator;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;

uses(TestingFrontend::class);

it('renders gallery widget on page', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $creator = resolve(WidgetCreator::class);

    /** @var \Illuminate\Support\Collection<int, mixed> $languages */
    $languages = CapellCore::getModel(CoreModelEnum::Language)::query()->get();

    $widget = $creator->galleryWidget(null, $languages);

    $layout = (new LayoutFactory)->widgets([$widget])->create();

    $page = Page::factory()->site($site)->layout($layout)->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertElementExists('.widget-gallery', fn (AssertElement $elm) => $elm);
});
