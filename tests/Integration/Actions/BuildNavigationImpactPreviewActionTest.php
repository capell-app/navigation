<?php

declare(strict_types=1);

use Capell\Admin\Support\PageUrlPresenter;
use Capell\Core\Data\EditorImpact\EditorImpactPreviewData;
use Capell\Core\Data\PageVariationData;
use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Events\Models\Event;
use Capell\Navigation\Actions\BuildNavigationImpactPreviewAction;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

uses(CreatesAdminUser::class)
    ->group('navigation');

beforeEach(function (): void {
    Language::factory()->default()->create();

    test()->actingAsAdmin();
});

it('previews accessible pages and active public URLs in the navigation scope', function (): void {
    $language = Language::query()->firstOrFail();
    $site = Site::factory()->language($language)->withTranslations()->create();
    $page = Page::factory()->site($site)->withTranslations($language)->create([
        'name' => 'Home',
    ]);
    $pageUrl = PageUrl::factory()->page($page)->site($site)->create([
        'language_id' => $language->getKey(),
        'url' => '/home',
        'status' => true,
    ]);

    $siteId = $site->getKey();
    $languageId = $language->getKey();
    throw_unless(is_int($siteId) && is_int($languageId), RuntimeException::class, 'Expected integer site and language IDs.');

    $navigation = Navigation::factory()
        ->site($site)
        ->language($language)
        ->items([
            [
                'type' => NavigationItemType::Page->value,
                'data' => [
                    'pageable_id' => $page->getKey(),
                    'pageable_type' => $page->getMorphClass(),
                ],
                'children' => [],
            ],
        ])
        ->create();

    $preview = BuildNavigationImpactPreviewAction::run($navigation, $siteId, $languageId);

    expect($preview)->toBeInstanceOf(EditorImpactPreviewData::class);
    assert($preview instanceof EditorImpactPreviewData);

    expect($preview->pageCount)->toBe(1)
        ->and($preview->siteCount)->toBe(1)
        ->and($preview->localeCount)->toBe(1)
        ->and($preview->pages[0]->name)->toBe('Home')
        ->and($preview->pages[0]->locales)->toBe([$language->code])
        ->and($preview->pages[0]->urls[0]->url)->toBe(PageUrlPresenter::displayUrl($pageUrl));
});

it('previews registered pageable models that are not core pages', function (): void {
    CapellCore::registerPageVariation(new PageVariationData(
        name: 'impact-event',
        model: Event::class,
        resourceName: 'impact-event',
    ));
    Relation::morphMap(['impact_event' => Event::class], merge: true);
    $globalScopes = Event::getAllGlobalScopes();
    Event::addGlobalScope(
        'navigation-impact-preview-pages',
        static fn (Builder $query): Builder => $query->from('pages as events'),
    );

    try {
        $language = Language::query()->firstOrFail();
        $site = Site::factory()->language($language)->withTranslations()->create();
        $page = Page::factory()->site($site)->withTranslations($language)->create(['name' => 'A pageable event']);
        PageUrl::factory()->create([
            'pageable_type' => 'impact_event',
            'pageable_id' => $page->getKey(),
            'site_id' => $site->getKey(),
            'language_id' => $language->getKey(),
            'url' => '/a-pageable-event',
            'status' => true,
        ]);

        $navigation = Navigation::factory()->site($site)->language($language)->create();

        $siteId = $site->getKey();
        $languageId = $language->getKey();
        throw_unless(is_int($siteId) && is_int($languageId), RuntimeException::class, 'Expected integer site and language IDs.');

        $preview = BuildNavigationImpactPreviewAction::run(
            $navigation,
            $siteId,
            $languageId,
        );

        expect($preview)->toBeInstanceOf(EditorImpactPreviewData::class);
        assert($preview instanceof EditorImpactPreviewData);

        expect($preview->pageCount)->toBe(1)
            ->and($preview->pages[0]->name)->toBe('A pageable event')
            ->and($preview->pages[0]->urls[0]->url)->toContain('/a-pageable-event');
    } finally {
        Event::setAllGlobalScopes($globalScopes);
    }
});

it('limits the preview to the navigation language and excludes redirect URLs', function (): void {
    $english = Language::query()->firstOrFail();
    $french = Language::factory()->create(['code' => 'fr']);
    $site = Site::factory()->language($english)->withTranslations()->create();
    $page = Page::factory()->site($site)->withTranslations($english)->create();
    $page->pageUrls()->update(['type' => UrlTypeEnum::Redirect]);
    $englishUrl = PageUrl::factory()->page($page)->site($site)->create([
        'language_id' => $english->getKey(),
        'url' => '/home',
        'status' => true,
    ]);
    PageUrl::factory()->page($page)->site($site)->create([
        'language_id' => $french->getKey(),
        'url' => '/fr/home',
        'status' => true,
        'type' => UrlTypeEnum::Redirect,
    ]);

    $redirectOnlyPage = Page::factory()->site($site)->withTranslations($english)->create();
    $redirectOnlyPage->pageUrls()->update(['type' => UrlTypeEnum::Redirect]);
    PageUrl::factory()->page($redirectOnlyPage)->site($site)->create([
        'language_id' => $english->getKey(),
        'url' => '/redirect-only',
        'status' => true,
        'type' => UrlTypeEnum::Redirect,
    ]);

    $navigation = Navigation::factory()
        ->site($site)
        ->language($english)
        ->create();

    assert(is_int($siteId = $site->getKey()));
    assert(is_int($languageId = $english->getKey()));

    $preview = BuildNavigationImpactPreviewAction::run($navigation, $siteId, $languageId);

    expect($preview)->toBeInstanceOf(EditorImpactPreviewData::class);
    assert($preview instanceof EditorImpactPreviewData);

    expect($preview->pageCount)->toBe(1)
        ->and($preview->localeCount)->toBe(1)
        ->and($preview->pages[0]->locales)->toBe([$english->code])
        ->and($preview->pages[0]->urls)->toHaveCount(1)
        ->and($preview->pages[0]->urls[0]->url)->toBe(PageUrlPresenter::displayUrl($englishUrl));
});

it('uses the current site and language state instead of the saved navigation scope', function (): void {
    $savedLanguage = Language::query()->firstOrFail();
    $currentLanguage = Language::factory()->create(['code' => 'fr']);
    $savedSite = Site::factory()->language($savedLanguage)->withTranslations($savedLanguage)->create();
    $currentSite = Site::factory()->language($currentLanguage)->withTranslations($currentLanguage)->create();

    $savedPage = Page::factory()->site($savedSite)->withTranslations($savedLanguage)->create(['name' => 'Saved scope']);
    PageUrl::factory()->page($savedPage)->site($savedSite)->create([
        'language_id' => $savedLanguage->getKey(),
        'url' => '/saved-scope',
        'status' => true,
    ]);

    $currentPage = Page::factory()->site($currentSite)->withTranslations($currentLanguage)->create(['name' => 'Current scope']);
    PageUrl::factory()->page($currentPage)->site($currentSite)->create([
        'language_id' => $currentLanguage->getKey(),
        'url' => '/current-scope',
        'status' => true,
    ]);

    $navigation = Navigation::factory()
        ->site($savedSite)
        ->language($savedLanguage)
        ->create();

    $preview = BuildNavigationImpactPreviewAction::run(
        $navigation,
        $currentSite->getKey(),
        $currentLanguage->getKey(),
    );

    expect($preview)->toBeInstanceOf(EditorImpactPreviewData::class);
    assert($preview instanceof EditorImpactPreviewData);

    expect($preview->pageCount)->toBe(1)
        ->and($preview->pages[0]->name)->toBe('Current scope');
});

it('matches the public publication, blueprint, accessibility, translation, and domain gates', function (): void {
    $language = Language::query()->firstOrFail();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $validPage = Page::factory()->site($site)->withTranslations($language)->create(['name' => 'Public page']);
    PageUrl::factory()->page($validPage)->site($site)->create([
        'language_id' => $language->getKey(),
        'url' => '/public-page',
        'status' => true,
    ]);

    $pendingPage = Page::factory()->site($site)->withTranslations($language)->create([
        'visible_from' => now()->addDay(),
    ]);
    PageUrl::factory()->page($pendingPage)->site($site)->create([
        'language_id' => $language->getKey(),
        'url' => '/pending-page',
        'status' => true,
    ]);

    $inaccessibleType = Blueprint::factory()->page()->create(['meta' => ['accessible' => false]]);
    $inaccessiblePage = Page::factory()->site($site)->type($inaccessibleType)->withTranslations($language)->create();
    PageUrl::factory()->page($inaccessiblePage)->site($site)->create([
        'language_id' => $language->getKey(),
        'url' => '/inaccessible-page',
        'status' => true,
    ]);

    $disabledDomainSite = Site::factory()->language($language)->withTranslations($language)->create();
    $disabledDomainSite->siteDomains()->update(['status' => false]);
    $disabledDomainPage = Page::factory()->site($disabledDomainSite)->withTranslations($language)->create();
    PageUrl::factory()->page($disabledDomainPage)->site($disabledDomainSite)->create([
        'language_id' => $language->getKey(),
        'url' => '/disabled-domain-page',
        'status' => true,
    ]);

    $navigation = Navigation::factory()->language($language)->create(['site_id' => null]);
    assert(is_int($languageId = $language->getKey()));
    $preview = BuildNavigationImpactPreviewAction::run($navigation, null, $languageId);

    expect($preview)->toBeInstanceOf(EditorImpactPreviewData::class);
    assert($preview instanceof EditorImpactPreviewData);

    expect($preview->pageCount)->toBe(1)
        ->and($preview->pages[0]->name)->toBe('Public page');
});

it('excludes redirect-only and malformed URL entries from counts and links', function (): void {
    $language = Language::query()->firstOrFail();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $redirectPage = Page::factory()->site($site)->withTranslations($language)->create(['name' => 'Redirect only']);
    $redirectPage->pageUrls()->update(['type' => UrlTypeEnum::Redirect]);
    $malformedPage = Page::factory()->site($site)->withTranslations($language)->create(['name' => 'Malformed']);
    $malformedPage->pageUrls()->update(['url' => 'javascript:alert(1)']);

    $navigation = Navigation::factory()->site($site)->language($language)->create();
    assert(is_int($siteId = $site->getKey()));
    assert(is_int($languageId = $language->getKey()));
    $preview = BuildNavigationImpactPreviewAction::run($navigation, $siteId, $languageId);

    expect($preview)->toBeInstanceOf(EditorImpactPreviewData::class);
    assert($preview instanceof EditorImpactPreviewData);

    expect($preview->pageCount)->toBe(0)
        ->and($preview->siteCount)->toBe(0)
        ->and($preview->localeCount)->toBe(0)
        ->and($preview->pages)->toBe([]);
});

it('does not expose an impact preview to an unauthenticated actor', function (): void {
    auth()->logout();

    $navigation = Navigation::factory()->create();

    expect(BuildNavigationImpactPreviewAction::run($navigation, null, null))->toBeNull();
});
