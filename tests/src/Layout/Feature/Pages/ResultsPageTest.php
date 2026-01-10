<?php

declare(strict_types=1);

use Capell\Admin\Enums\LayoutEnum;
use Capell\Admin\Support\Creator\LayoutCreator;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Tests\Fixtures\Support\Concerns\TestingFrontend;

use function Pest\Laravel\get;

use Sinnbeck\DomAssertions\Asserts\AssertElement;
use Sinnbeck\DomAssertions\Asserts\BaseAssert;

uses(TestingFrontend::class);

test('results page with layout', function (): void {
    $site = Site::factory()->withTranslations()->create();

    $layoutCreator = resolve(LayoutCreator::class);
    $layout = $layoutCreator->create(LayoutEnum::Results);

    $page = Page::factory()->site($site)->layout($layout)->home()->withTranslations()->create();

    get($page->pageUrl->full_url)
        ->assertOk()
        ->assertSeeHtml('<title>' . e($page->translation->title) . ' | ' . e($site->title) . '</title>')
        ->assertElementExists(
            'h1',
            fn (AssertElement $elm): BaseAssert => $elm->containsText(e($page->translation->title)),
        );
});
