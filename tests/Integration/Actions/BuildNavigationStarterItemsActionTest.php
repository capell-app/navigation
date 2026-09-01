<?php

declare(strict_types=1);

use Capell\Core\Data\PageVariationData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Navigation\Actions\BuildNavigationStarterItemsAction;
use Capell\Navigation\Data\NavigationStarterItemsData;
use Capell\Navigation\Data\NavigationStarterRequestData;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Support\Creator\NavigationCreator;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

uses(CreatesAdminUser::class)
    ->group('navigation');

/**
 * A default language and a site that actually offers it, as an installed
 * Capell App would have.
 *
 * @return array{0: Site, 1: Language}
 */
function starterFixture(): array
{
    $language = Language::factory()->default()->create();
    $site = Site::factory()->default()->language($language)->withTranslations()->create();

    test()->actingAsAdmin();

    return [$site, $language];
}

/**
 * Publish a page with an active, non-redirect public URL on the given site.
 *
 * @param  array<string, mixed>  $attributes
 */
function starterPage(Site $site, Language $language, string $name, array $attributes = []): Page
{
    $page = Page::factory()
        ->site($site)
        ->withTranslations($language)
        ->create(['name' => $name, ...$attributes]);

    PageUrl::factory()->page($page)->site($site)->create([
        'language_id' => $language->getKey(),
        'url' => '/' . Str::slug($name),
        'status' => true,
    ]);

    return $page;
}

/**
 * The label the starter is expected to use: the page's translated navigation
 * label, exactly as a hand-added page item would resolve it.
 */
function starterLabel(Page $page, Language $language): string
{
    return NavigationCreator::getPageNavigationLabel($page->loadMissing('translations', 'site'), $language);
}

function starterKey(Model $model): int
{
    $key = $model->getKey();

    throw_unless(is_int($key), RuntimeException::class, 'Expected an integer model key.');

    return $key;
}

/**
 * @param  array<string, mixed>  $item
 * @return array<string, mixed>
 */
function starterItemData(array $item): array
{
    $data = $item['data'] ?? [];

    return is_array($data) ? $data : [];
}

function starterFor(Site $site, Language $language): NavigationStarterItemsData
{
    return BuildNavigationStarterItemsAction::run(
        new NavigationStarterRequestData(
            siteId: starterKey($site),
            languageId: starterKey($language),
        ),
    );
}

/**
 * @return list<string|null>
 */
function starterLabels(Site $site, Language $language): array
{
    return array_values(array_map(
        fn (array $item): ?string => is_string($item['label'] ?? null) ? $item['label'] : null,
        starterFor($site, $language)->items,
    ));
}

it('builds ordinary page items from the published top-level pages', function (): void {
    [$site, $language] = starterFixture();

    $about = starterPage($site, $language, 'About');
    $contact = starterPage($site, $language, 'Contact');

    $items = starterFor($site, $language)->items;

    expect($items)->toHaveCount(2);

    foreach ($items as $item) {
        expect($item['type'])->toBe(NavigationItemType::Page->value)
            ->and($item['children'])->toBe([])
            ->and($item['is_visible'])->toBeTrue()
            ->and(starterItemData($item)['pageable_type'])->toBe(resolve(Page::class)->getMorphClass())
            ->and(starterItemData($item)['site_id'])->toBe(starterKey($site));
    }

    expect(array_keys($items))->each->toBeString();

    $labels = starterLabels($site, $language);
    sort($labels);

    $expected = [starterLabel($about, $language), starterLabel($contact, $language)];
    sort($expected);

    expect($labels)->toBe($expected);
});

it('reports how many pages it added', function (): void {
    [$site, $language] = starterFixture();

    starterPage($site, $language, 'About');

    $starter = starterFor($site, $language);

    expect($starter->pageCount)->toBe(1)
        ->and($starter->isEmpty())->toBeFalse();
});

it('excludes child pages so the starter is a top-level menu', function (): void {
    [$site, $language] = starterFixture();

    $parent = starterPage($site, $language, 'About');
    starterPage($site, $language, 'Team', ['parent_id' => $parent->getKey()]);

    expect(starterLabels($site, $language))->toBe([starterLabel($parent, $language)]);
});

it('excludes unpublished pages', function (): void {
    [$site, $language] = starterFixture();

    $about = starterPage($site, $language, 'About');
    starterPage($site, $language, 'Draft', ['visible_from' => now()->addMonth()]);

    expect(starterLabels($site, $language))->toBe([starterLabel($about, $language)]);
});

it('excludes pages whose page type is disabled', function (): void {
    [$site, $language] = starterFixture();

    $about = starterPage($site, $language, 'About');

    $hiddenType = Blueprint::factory()->page()->create(['status' => false]);
    starterPage($site, $language, 'Hidden', ['blueprint_id' => $hiddenType->getKey()]);

    expect(starterLabels($site, $language))->toBe([starterLabel($about, $language)]);
});

it('excludes pages without an active public URL', function (): void {
    [$site, $language] = starterFixture();

    $about = starterPage($site, $language, 'About');
    $disabled = starterPage($site, $language, 'Disabled URL');

    PageUrl::query()
        ->where('pageable_id', $disabled->getKey())
        ->where('pageable_type', $disabled->getMorphClass())
        ->update(['status' => false]);

    expect(starterLabels($site, $language))->toBe([starterLabel($about, $language)]);
});

it('excludes a page whose active URL belongs to another site', function (): void {
    [$site, $language] = starterFixture();
    $otherSite = Site::factory()->language($language)->withTranslations()->create();
    $page = Page::factory()->site($site)->withTranslations($language)->create(['name' => 'About']);

    PageUrl::withoutEvents(function () use ($page, $otherSite): void {
        PageUrl::query()
            ->where('pageable_id', $page->getKey())
            ->where('pageable_type', $page->getMorphClass())
            ->update(['site_id' => $otherSite->getKey()]);
    });

    expect(PageUrl::query()
        ->where('pageable_id', $page->getKey())
        ->where('pageable_type', $page->getMorphClass())
        ->value('site_id'))->toBe($otherSite->getKey());

    expect(starterFor($site, $language)->items)->toBe([]);
});

it('stays inside the requested site', function (): void {
    [$site, $language] = starterFixture();

    $about = starterPage($site, $language, 'About');

    $otherSite = Site::factory()->language($language)->withTranslations()->create();
    starterPage($otherSite, $language, 'Other');

    expect(starterLabels($site, $language))->toBe([starterLabel($about, $language)]);
});

it('returns nothing without a site scope', function (): void {
    [$site, $language] = starterFixture();

    starterPage($site, $language, 'About');

    $starter = BuildNavigationStarterItemsAction::run(new NavigationStarterRequestData);

    expect($starter->isEmpty())->toBeTrue()
        ->and($starter->pageCount)->toBe(0);
});

it('returns nothing for a non-positive item limit', function (int $limit): void {
    [$site, $language] = starterFixture();

    starterPage($site, $language, 'About');

    $starter = BuildNavigationStarterItemsAction::run(new NavigationStarterRequestData(
        siteId: starterKey($site),
        languageId: starterKey($language),
        limit: $limit,
    ));

    expect($starter->isEmpty())->toBeTrue()
        ->and($starter->pageCount)->toBe(0);
})->with([0, -1]);

it('ignores a registered non-page model before page-only queries', function (): void {
    [$site, $language] = starterFixture();

    $about = starterPage($site, $language, 'About');

    CapellCore::registerPageVariation(new PageVariationData(
        name: 'navigation-blueprint-model',
        model: Blueprint::class,
        resourceName: 'navigation-blueprint-model',
    ));

    expect(starterLabels($site, $language))->toBe([starterLabel($about, $language)]);
});

it('returns nothing without an authenticated actor', function (): void {
    [$site, $language] = starterFixture();

    starterPage($site, $language, 'About');

    auth()->logout();

    expect(starterFor($site, $language)->items)->toBe([]);
});

it('orders items by the page order', function (): void {
    [$site, $language] = starterFixture();

    $third = starterPage($site, $language, 'Third', ['order' => 3]);
    $first = starterPage($site, $language, 'First', ['order' => 1]);
    $second = starterPage($site, $language, 'Second', ['order' => 2]);

    expect(starterLabels($site, $language))->toBe([
        starterLabel($first, $language),
        starterLabel($second, $language),
        starterLabel($third, $language),
    ]);
});
