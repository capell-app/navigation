<?php

declare(strict_types=1);

use Capell\Core\Actions\ApplySiteSpecToSiteAction;
use Capell\Core\Actions\BuildCapellSiteFromSpecAction;
use Capell\Core\Actions\ExportSiteSpecAction;
use Capell\Core\Contracts\SiteSpec\SiteSpecExporter;
use Capell\Core\Data\SiteSpec\CapellSiteSpecData;
use Capell\Core\Data\SiteSpec\CapellSiteSpecNavigationData;
use Capell\Core\Data\SiteSpec\SiteSpecExportPartData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Support\SiteSpec\SiteSpecExporterRegistry;
use Capell\Navigation\Actions\ApplyNavigationSiteSpecAction;
use Capell\Navigation\Actions\ExportNavigationSiteSpecAction;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Navigation\Providers\NavigationServiceProvider;
use Capell\Navigation\Support\Creator\NavigationCreator;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function exportedPageItem(Page $page, Site $site, Language $language, array $overrides = []): array
{
    return array_replace([
        'label' => NavigationCreator::getPageNavigationLabel($page, $language),
        'type' => NavigationItemType::Page->value,
        'data' => [
            'site_id' => $site->getKey(),
            'pageable_id' => $page->getKey(),
            'pageable_type' => $page->getMorphClass(),
        ],
        'children' => [],
    ], $overrides);
}

/** @return list<array<string, mixed>> */
function exportedNavigations(SiteSpecExportPartData $part): array
{
    return array_map(fn (CapellSiteSpecNavigationData $navigation): array => $navigation->toArray(), $part->navigations);
}

/** @param list<array<string, mixed>> $navigations */
function navigationSpec(array $navigations): CapellSiteSpecData
{
    return CapellSiteSpecData::from([
        'site' => ['name' => 'Harbour Books'],
        'theme' => ['key' => 'default'],
        'pages' => [],
        'navigations' => $navigations,
    ]);
}

/** @return array<int, mixed> */
function navigationSnapshot(Site $site): array
{
    return Navigation::query()->whereBelongsTo($site)->orderBy('key')->get()
        ->map(fn (Navigation $navigation): array => [$navigation->name, $navigation->getRawOriginal('items')])
        ->all();
}

beforeEach(function (): void {
    $this->blueprint = Blueprint::factory()->navigation()->create(['key' => 'navigation']);
    $this->language = Language::factory()->default()->create();
    $this->site = Site::factory()->language($this->language)->withTranslations(siteDomainData: ['domain' => 'harbour.test'])->create();
    $this->home = Page::factory()->site($this->site)->home()->withTranslations($this->language, slug: '/')->create(['name' => 'Home']);
    $this->about = Page::factory()->site($this->site)->withTranslations($this->language, slug: '/about')->create(['name' => 'About']);
    $this->slugs = [$this->home->getKey() => 'home', $this->about->getKey() => 'about'];
});

it('registers the navigation exporter through the stable core tag with the applier key', function (): void {
    expect(app()->tagged(SiteSpecExporter::TAG))->toContainEqual(resolve(ExportNavigationSiteSpecAction::class))
        ->and(collect(resolve(SiteSpecExporterRegistry::class)->all())->map->key()->all())->toContain('navigation')
        ->and(resolve(ExportNavigationSiteSpecAction::class)->key())->toBe(resolve(ApplyNavigationSiteSpecAction::class)->key());
});

it('round-trips navigations through the applier with no change, onto the same and a second site', function (): void {
    Navigation::factory()->site($this->site)->language($this->language)->create(['blueprint_id' => $this->blueprint->getKey(),
        'key' => 'main', 'name' => 'Primary', 'meta' => null,
        'items' => [exportedPageItem($this->about, $this->site, $this->language), exportedPageItem($this->home, $this->site, $this->language)],
    ]);
    Navigation::factory()->site($this->site)->language($this->language)->create(['blueprint_id' => $this->blueprint->getKey(),
        'key' => 'footer', 'name' => 'Footer', 'meta' => null,
        'items' => [exportedPageItem($this->home, $this->site, $this->language)],
    ]);

    $part = ExportNavigationSiteSpecAction::run($this->site, $this->slugs, false);

    expect($part->omitted)->toBe([])
        ->and(exportedNavigations($part))->toBe([
            ['key' => 'footer', 'pageSlugs' => ['home'], 'name' => 'Footer'],
            ['key' => 'main', 'pageSlugs' => ['about', 'home'], 'name' => 'Primary'],
        ]);

    $before = navigationSnapshot($this->site);
    ApplyNavigationSiteSpecAction::run(navigationSpec(exportedNavigations($part)), $this->site, ['home' => $this->home, 'about' => $this->about]);
    expect(navigationSnapshot($this->site))->toBe($before);

    $secondSite = Site::factory()->language($this->language)->withTranslations(siteDomainData: ['domain' => 'second.test'])->create();
    $secondHome = Page::factory()->site($secondSite)->home()->withTranslations($this->language, slug: '/')->create(['name' => 'Home']);
    $secondAbout = Page::factory()->site($secondSite)->withTranslations($this->language, slug: '/about')->create(['name' => 'About']);
    ApplyNavigationSiteSpecAction::run(navigationSpec(exportedNavigations($part)), $secondSite, ['home' => $secondHome, 'about' => $secondAbout]);

    $reexport = ExportNavigationSiteSpecAction::run($secondSite, [$secondHome->getKey() => 'home', $secondAbout->getKey() => 'about'], false);
    expect(exportedNavigations($reexport))->toBe(exportedNavigations($part))
        ->and($reexport->omitted)->toBe([]);
});

it('leaves out items the applier cannot rebuild and says why', function (): void {
    $outside = Page::factory()->site($this->site)->withTranslations($this->language, slug: '/outside')->create(['name' => 'Outside']);
    Navigation::factory()->site($this->site)->language($this->language)->create(['blueprint_id' => $this->blueprint->getKey(),
        'key' => 'main', 'name' => 'Primary', 'meta' => null,
        'items' => [
            exportedPageItem($this->home, $this->site, $this->language),
            ['label' => 'Docs', 'type' => NavigationItemType::ExternalLink->value, 'data' => ['url' => 'https://example.test'], 'children' => []],
            exportedPageItem($outside, $this->site, $this->language),
            exportedPageItem($this->about, $this->site, $this->language, ['label' => 'Our story']),
            exportedPageItem($this->about, $this->site, $this->language, ['children' => [exportedPageItem($this->home, $this->site, $this->language)]]),
            exportedPageItem($this->about, $this->site, $this->language, ['is_visible' => false]),
            exportedPageItem($this->about, $this->site, $this->language, ['data' => ['pageable_id' => $this->about->getKey(), 'pageable_type' => $this->about->getMorphClass(), 'anchor' => 'team']]),
            exportedPageItem($this->home, $this->site, $this->language),
        ],
    ]);

    $part = ExportNavigationSiteSpecAction::run($this->site, $this->slugs, false);

    expect(exportedNavigations($part))->toBe([['key' => 'main', 'pageSlugs' => ['home'], 'name' => 'Primary']])
        ->and($part->omitted)->toBe([
            'Navigation [main] item 2 "Docs": it is a [external_link] item; a SiteSpec only carries page links.',
            'Navigation [main] item 3 "Outside en": its page is not in this export.',
            'Navigation [main] item 5 "About en": it has child items; a SiteSpec only carries a flat list.',
            'Navigation [main] item 6 "About en": it is hidden, which a SiteSpec cannot carry.',
            'Navigation [main] item 7 "About en": it has link settings a SiteSpec cannot carry.',
            'Navigation [main] item 4 "Our story": it has a custom label, which a SiteSpec cannot carry.',
            'Navigation [main] item 8 "Home en": it repeats a page already in this navigation, which a SiteSpec cannot carry.',
        ]);
});

it('leaves out items with extra fields, item keys, other-site links or deleted pages', function (): void {
    $otherSite = Site::factory()->language($this->language)->withTranslations(siteDomainData: ['domain' => 'other.test'])->create();
    $gone = Page::factory()->site($this->site)->withTranslations($this->language, slug: '/gone')->create(['name' => 'Gone']);
    $navigation = Navigation::factory()->site($this->site)->language($this->language)->create(['blueprint_id' => $this->blueprint->getKey(),
        'key' => 'main', 'name' => 'Primary', 'meta' => null, 'items' => []]);
    // Written raw: the model's items cast drops unknown fields on save, but stored JSON from older rows can still hold them.
    Navigation::query()->whereKey($navigation->getKey())->update([
        'items' => json_encode([
            exportedPageItem($this->home, $this->site, $this->language),
            exportedPageItem($this->home, $this->site, $this->language, ['icon' => 'x']),
            exportedPageItem($this->about, $this->site, $this->language, ['key' => 'abc']),
            exportedPageItem($this->about, $this->site, $this->language, ['data' => ['site_id' => $otherSite->getKey(), 'pageable_id' => $this->about->getKey(), 'pageable_type' => $this->about->getMorphClass()]]),
            exportedPageItem($gone, $this->site, $this->language),
        ], JSON_THROW_ON_ERROR),
    ]);
    $slugs = $this->slugs + [$gone->getKey() => 'gone'];
    $gone->delete();

    $part = ExportNavigationSiteSpecAction::run($this->site, $slugs, false);
    $omitted = implode("\n", $part->omitted);

    expect(exportedNavigations($part))->toBe([['key' => 'main', 'pageSlugs' => ['home'], 'name' => 'Primary']])
        ->and($part->omitted)->toHaveCount(4)
        ->and($omitted)->toContain('item 2 "Home en": it has fields a SiteSpec cannot carry.')
        ->and($omitted)->toContain('item 3 "About en": it has an item key, which a SiteSpec cannot carry.')
        ->and($omitted)->toContain('item 4 "About en": it links to a page on another site.')
        ->and($omitted)->toContain('item 5 "Gone en": its page no longer exists.');
});

it('leaves out a navigation that uses another blueprint', function (): void {
    $mega = Blueprint::factory()->navigation()->create(['key' => 'mega']);
    Navigation::factory()->site($this->site)->language($this->language)->create(['blueprint_id' => $mega->getKey(), 'key' => 'main', 'meta' => null, 'items' => [exportedPageItem($this->home, $this->site, $this->language)]]);

    $part = ExportNavigationSiteSpecAction::run($this->site, $this->slugs, false);

    expect($part->navigations)->toBe([])
        ->and($part->omitted)->toBe(['Navigation [main]: it uses the [mega] blueprint; a SiteSpec only restores the default navigation blueprint.']);
});

it('leaves out whole navigations the applier cannot rebuild and says why', function (): void {
    $french = Language::factory()->create(['code' => 'fr']);
    Navigation::factory()->site($this->site)->language($french)->create(['blueprint_id' => $this->blueprint->getKey(), 'key' => 'a-french', 'meta' => null, 'items' => []]);
    Navigation::factory()->site($this->site)->language($this->language)->create(['blueprint_id' => $this->blueprint->getKey(), 'key' => 'b-window', 'meta' => null, 'visible_until' => now()->addDay(), 'items' => []]);
    Navigation::factory()->site($this->site)->language($this->language)->create(['blueprint_id' => $this->blueprint->getKey(), 'key' => 'c-meta', 'meta' => ['css_class' => 'x'], 'items' => []]);

    $part = ExportNavigationSiteSpecAction::run($this->site, $this->slugs, false);

    expect($part->navigations)->toBe([])
        ->and($part->omitted)->toBe([
            'Navigation [a-french]: it is not in the site language; a SiteSpec only carries site-language navigations.',
            'Navigation [b-window]: it has a visibility window, which a SiteSpec cannot carry.',
            'Navigation [c-meta]: it has settings (meta), which a SiteSpec cannot carry.',
        ]);
});

it('leaves pages visitors cannot see out of a public export only', function (): void {
    Navigation::factory()->site($this->site)->language($this->language)->create(['blueprint_id' => $this->blueprint->getKey(),
        'key' => 'main', 'name' => 'Primary', 'meta' => null,
        'items' => [exportedPageItem($this->home, $this->site, $this->language), exportedPageItem($this->about, $this->site, $this->language)],
    ]);
    $this->about->update(['visible_from' => now()->addDay()]);

    $private = ExportNavigationSiteSpecAction::run($this->site, $this->slugs, false);
    $public = ExportNavigationSiteSpecAction::run($this->site, $this->slugs, true);

    expect(exportedNavigations($private)[0]['pageSlugs'])->toBe(['home', 'about'])
        ->and(exportedNavigations($public)[0]['pageSlugs'])->toBe(['home'])
        ->and($public->omitted)->toBe(['Navigation [main] item 2 "About en": its page is not public, so it is left out of a public export.']);
});

it('exports nothing while Navigation is disabled', function (): void {
    Navigation::factory()->site($this->site)->language($this->language)->create(['blueprint_id' => $this->blueprint->getKey(), 'key' => 'main', 'meta' => null, 'items' => [exportedPageItem($this->home, $this->site, $this->language)]]);

    CapellCore::markPackageDisabled(NavigationServiceProvider::$packageName);
    $disabled = ExportNavigationSiteSpecAction::run($this->site, $this->slugs, false);
    CapellCore::markPackageInstalled(NavigationServiceProvider::$packageName);

    expect($disabled->navigations)->toBe([])->and($disabled->omitted)->toBe([])
        ->and(ExportNavigationSiteSpecAction::run($this->site, $this->slugs, false)->navigations)->toHaveCount(1);
});

it('round-trips suffix labels and reports unsupported menus through Core export and both apply paths', function (): void {
    $site = BuildCapellSiteFromSpecAction::run(CapellSiteSpecData::validateAndCreate([
        'site' => ['name' => 'Harbour Books'],
        'theme' => ['key' => 'default'],
        'pages' => [
            ['name' => 'Home', 'slug' => 'home', 'title' => 'Home | Harbour Books', 'pageType' => 'default', 'order' => 0, 'sections' => [['type' => 'content', 'content' => '<p>Browse our books and reading groups.</p>']]],
            ['name' => 'About', 'slug' => 'about', 'title' => 'About | Harbour Books', 'pageType' => 'default', 'order' => 1, 'sections' => [['type' => 'content', 'content' => '<p>Meet our booksellers and community.</p>']]],
        ],
        'navigations' => [['key' => 'main', 'name' => 'Primary', 'pageSlugs' => ['home', 'about']]],
        'initialVisibility' => 'public',
        'acknowledgePublic' => true,
    ]));
    $language = $site->language;
    throw_unless($language instanceof Language, RuntimeException::class, 'The fixture site must have a language.');
    Navigation::factory()->site($site)->language($language)->create([
        'blueprint_id' => $this->blueprint->id, 'key' => 'external-only', 'meta' => null,
        'items' => [['label' => 'Catalogue', 'type' => NavigationItemType::ExternalLink->value, 'data' => ['url' => 'https://example.test/catalogue'], 'children' => []]],
    ]);
    $before = navigationSnapshot($site);
    $export = ExportSiteSpecAction::run($site, true);

    expect($export->spec->navigations[0]->pageSlugs)->toBe(['home', 'about'])
        ->and($export->omittedParts['navigation'] ?? [])->toBe([
            'Navigation [external-only] item 1 "Catalogue": it is a [external_link] item; a SiteSpec only carries page links.',
            'Navigation [external-only]: it has no page links that a SiteSpec can restore.',
        ]);

    ApplySiteSpecToSiteAction::run($export->spec, $site->refresh());

    expect(navigationSnapshot($site))->toBe($before);

    // Give the import a new identity while retaining the same name for suffix matching.
    $site->update(['name' => 'Archived source site']);
    $site->siteDomains->each(fn (SiteDomain $domain): bool => $domain->update(['domain' => 'archived-source.test']));

    $secondSpec = CapellSiteSpecData::validateAndCreate([
        ...$export->spec->toArray(),
        'site' => [...$export->spec->site->toArray(), 'businessName' => 'Second Harbour Books import'],
    ]);
    $secondSite = BuildCapellSiteFromSpecAction::run($secondSpec);
    expect($secondSite->getKey())->not->toBe($site->getKey());
    $secondExport = ExportSiteSpecAction::run($secondSite, true);

    expect(array_map(fn (CapellSiteSpecNavigationData $navigation): array => $navigation->toArray(), $secondExport->spec->navigations))
        ->toBe(array_map(fn (CapellSiteSpecNavigationData $navigation): array => $navigation->toArray(), $export->spec->navigations))
        ->and($secondExport->omittedParts['navigation'] ?? [])->toBe([]);
});

it('leaves out stored active state the applier does not restore', function (bool $storedActive): void {
    Navigation::factory()->site($this->site)->language($this->language)->create([
        'blueprint_id' => $this->blueprint->id, 'key' => 'main', 'meta' => null,
        'items' => [
            exportedPageItem($this->home, $this->site, $this->language),
            exportedPageItem($this->about, $this->site, $this->language, ['active' => $storedActive]),
        ],
    ]);

    $part = ExportNavigationSiteSpecAction::run($this->site, $this->slugs, false);

    expect($part->navigations[0]->pageSlugs)->toBe(['home'])
        ->and($part->omitted)->toBe(['Navigation [main] item 2 "About en": it has stored active state, which a SiteSpec cannot carry.']);
})->with([true, false]);
